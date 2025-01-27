<?php
declare(strict_types=1);

namespace Limepie\Thumbnail\Driver;

use Limepie\Thumbnail\DriverAbstract;

// src/Driver/GdDriver.php
class Gd extends DriverAbstract
{
    public function __destruct()
    {
        if ($this->image instanceof \GdImage) {
            \imagedestroy($this->image);
        }
    }

    public function createFromFile(string $filepath) : void
    {
        if (!\file_exists($filepath) || !\is_readable($filepath)) {
            throw new DriverException(
                "File not found or not readable: {$filepath}"
            );
        }

        $imageInfo = \getimagesize($filepath);

        if (false === $imageInfo) {
            throw new DriverException("Invalid image file: {$filepath}");
        }

        $this->image = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => \imagecreatefromjpeg($filepath),
            IMAGETYPE_PNG  => \imagecreatefrompng($filepath),
            IMAGETYPE_GIF  => \imagecreatefromgif($filepath),
            default        => throw new DriverException('Unsupported image type')
        };

        if (false === $this->image) {
            throw new DriverException('Failed to create image from file');
        }

        $this->originalDimensions = [
            'width'  => \imagesx($this->image),
            'height' => \imagesy($this->image),
        ];

        $this->preserveTransparency();
    }

    public function createFromString(string $data) : void
    {
        $this->image = \imagecreatefromstring($data);

        if (false === $this->image) {
            throw new DriverException('Failed to create image from string data');
        }

        $this->originalDimensions = [
            'width'  => \imagesx($this->image),
            'height' => \imagesy($this->image),
        ];

        $this->preserveTransparency();
    }

    protected function preserveTransparency() : void
    {
        \imagesavealpha($this->image, true);
        \imagealphablending($this->image, false);
    }

    protected function getImageDimensions() : array
    {
        return [
            'width'  => \imagesx($this->image),
            'height' => \imagesy($this->image),
        ];
    }

    public function resize(int $width, int $height) : void
    {
        $dimensions = $this->calculateDimensions($width, $height, false);

        // 배경 이미지 생성
        $canvas = \imagecreatetruecolor($width, $height);

        if (false === $canvas) {
            throw new DriverException('Failed to create canvas');
        }

        // 배경 투명도 설정
        \imagealphablending($canvas, false);
        \imagesavealpha($canvas, true);

        // 배경색 설정
        $background = $this->createBackgroundColor($canvas);
        \imagefill($canvas, 0, 0, $background);

        // 리사이징된 임시 이미지 생성
        $resized = \imagecreatetruecolor($dimensions['width'], $dimensions['height']);

        if (false === $resized) {
            \imagedestroy($canvas);

            throw new DriverException('Failed to create resized image');
        }

        // 리사이징된 이미지의 투명도 설정
        \imagealphablending($resized, false);
        \imagesavealpha($resized, true);

        // 리사이징 수행
        if (!\imagecopyresampled(
            $resized,
            $this->image,
            0,
            0,
            0,
            0,
            $dimensions['width'],
            $dimensions['height'],
            $this->originalDimensions['width'],
            $this->originalDimensions['height']
        )) {
            \imagedestroy($resized);
            \imagedestroy($canvas);

            throw new DriverException('Failed to resize image');
        }

        // 중앙 정렬하여 합성
        $left = (int) (($width - $dimensions['width']) / 2);
        $top  = (int) (($height - $dimensions['height']) / 2);

        \imagealphablending($canvas, true);

        if (!\imagecopy(
            $canvas,
            $resized,
            $left,
            $top,
            0,
            0,
            $dimensions['width'],
            $dimensions['height']
        )) {
            \imagedestroy($resized);
            \imagedestroy($canvas);

            throw new DriverException('Failed to copy image to canvas');
        }

        // 이전 이미지 해제
        \imagedestroy($this->image);
        \imagedestroy($resized);

        $this->image = $canvas;
    }

    public function crop(int $width, int $height) : void
    {
        $dimensions = $this->calculateDimensions($width, $height, true);

        // 리사이징된 임시 이미지 생성
        $resized = \imagecreatetruecolor($dimensions['width'], $dimensions['height']);

        if (false === $resized) {
            throw new DriverException('Failed to create resized image');
        }

        // 리사이징된 이미지의 투명도 설정
        \imagealphablending($resized, false);
        \imagesavealpha($resized, true);

        // 리사이징 수행
        if (!\imagecopyresampled(
            $resized,
            $this->image,
            0,
            0,
            0,
            0,
            $dimensions['width'],
            $dimensions['height'],
            $this->originalDimensions['width'],
            $this->originalDimensions['height']
        )) {
            \imagedestroy($resized);

            throw new DriverException('Failed to resize image');
        }

        // 최종 이미지 생성
        $final = \imagecreatetruecolor($width, $height);

        if (false === $final) {
            \imagedestroy($resized);

            throw new DriverException('Failed to create final image');
        }

        // 최종 이미지의 투명도 설정
        \imagealphablending($final, false);
        \imagesavealpha($final, true);

        // 중앙 기준 crop
        $left = (int) (($dimensions['width'] - $width) / 2);
        $top  = (int) (($dimensions['height'] - $height) / 2);

        if (!\imagecopy(
            $final,
            $resized,
            0,
            0,
            $left,
            $top,
            $width,
            $height
        )) {
            \imagedestroy($resized);
            \imagedestroy($final);

            throw new DriverException('Failed to crop image');
        }

        // 이전 이미지들 해제
        \imagedestroy($this->image);
        \imagedestroy($resized);

        $this->image = $final;
    }

    private function createBackgroundColor($image)
    {
        if ('transparent' === $this->options['background']) {
            return \imagecolorallocatealpha($image, 0, 0, 0, 127);
        }

        if (\is_array($this->options['background'])
            && 4 === \count($this->options['background'])) {
            return \imagecolorallocatealpha(
                $image,
                $this->options['background'][0],
                $this->options['background'][1],
                $this->options['background'][2],
                (int) (127 - ($this->options['background'][3] * 127 / 255))
            );
        }

        throw new DriverException('Invalid background color format');
    }

    public function saveToStream($stream, string $format = 'jpeg') : void
    {
        if (!$this->image) {
            throw new DriverException('No image to save. Create an image first.');
        }

        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid stream resource.');
        }

        \ob_start();
        $success = match (\strtolower($format)) {
            'jpeg', 'jpg' => \imagejpeg($this->image, null, $this->options['quality']),
            'png' => \imagepng(
                $this->image,
                null,
                \min(9, (int) (9 * $this->options['quality'] / 100))
            ),
            'gif'   => \imagegif($this->image),
            default => throw new DriverException("Unsupported format: {$format}")
        };

        if (!$success) {
            \ob_end_clean();

            throw new DriverException("Failed to save image as {$format}");
        }

        $data = \ob_get_clean();

        if (false === \fwrite($stream, $data)) {
            throw new DriverException('Failed to write to stream');
        }
    }

    public function getImageData(string $format = 'jpeg') : string
    {
        if (!$this->image) {
            throw new DriverException('No image data. Create an image first.');
        }

        \ob_start();
        $success = match (\strtolower($format)) {
            'jpeg', 'jpg' => \imagejpeg($this->image, null, $this->options['quality']),
            'png' => \imagepng(
                $this->image,
                null,
                \min(9, (int) (9 * $this->options['quality'] / 100))
            ),
            'gif'   => \imagegif($this->image),
            default => throw new DriverException("Unsupported format: {$format}")
        };

        if (!$success) {
            \ob_end_clean();

            throw new DriverException("Failed to get image data as {$format}");
        }

        $data = \ob_get_clean();

        if (false === $data) {
            throw new DriverException('Failed to get output buffer contents');
        }

        return $data;
    }
}
