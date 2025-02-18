<?php

declare(strict_types=1);

namespace Limepie;

class ThumbnailGd
{
    public const SCALE_EXACT_FIT = 'crop';

    public const SCALE_SHOW_ALL = 'scale';

    private $image;

    private int $width;

    private int $height;

    private string $scale;

    private array $options;

    public function __construct(int $width, int $height, string $scale = self::SCALE_SHOW_ALL, array $options = [])
    {
        $this->width   = $width;
        $this->height  = $height;
        $this->scale   = $scale;
        $this->options = \array_merge([
            'background' => 'transparent',
            'quality'    => 90,
            'enlarge'    => true, // 새로운 옵션: 원본보다 큰 크기 요청 시 확대 여부
        ], $options);

        $this->validateInput();
    }

    private function validateInput() : void
    {
        if ($this->width <= 0 || $this->height <= 0) {
            throw new \InvalidArgumentException('Invalid thumbnail size. Width and height must be positive.');
        }

        if (!\in_array($this->scale, [self::SCALE_EXACT_FIT, self::SCALE_SHOW_ALL])) {
            $this->scale = self::SCALE_SHOW_ALL;
        }
    }

    public function createFromFile(string $filepath) : self
    {
        if (!\file_exists($filepath) || !\is_readable($filepath)) {
            throw new \RuntimeException("File not found or not readable: {$filepath}");
        }

        $imageInfo = @\getimagesize($filepath);

        if (false === $imageInfo) {
            throw new \RuntimeException("Invalid image file: {$filepath}");
        }

        $this->image = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => \imagecreatefromjpeg($filepath),
            IMAGETYPE_PNG  => \imagecreatefrompng($filepath),
            IMAGETYPE_GIF  => \imagecreatefromgif($filepath),
            IMAGETYPE_WEBP => \imagecreatefromwebp($filepath),
            default        => throw new \RuntimeException("Unsupported image type: {$filepath}"),
        };

        if (!$this->image) {
            throw new \RuntimeException("Failed to create image from file: {$filepath}");
        }

        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    public function createFromString(string $data) : self
    {
        $this->image = @\imagecreatefromstring($data);

        if (!$this->image) {
            throw new \RuntimeException('Failed to create image from string data');
        }

        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    public function createFromResource($resource) : self
    {
        if (!($resource instanceof \GdImage) && !\is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid resource. Must be a GD resource or GdImage object.');
        }

        $this->image = $resource;
        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    private function preserveTransparency() : void
    {
        \imagesavealpha($this->image, true);
        \imagealphablending($this->image, false);
    }

    private function resizeImage() : void
    {
        $srcWidth  = \imagesx($this->image);
        $srcHeight = \imagesy($this->image);
        $srcRatio  = $srcWidth    / $srcHeight;
        $dstRatio  = $this->width / $this->height;

        if (self::SCALE_SHOW_ALL === $this->scale) {
            // Scale 모드
            if ($srcRatio > $dstRatio) {
                $newWidth  = $this->width;
                $newHeight = (int) ($this->width / $srcRatio);
            } else {
                $newHeight = $this->height;
                $newWidth  = (int) ($this->height * $srcRatio);
            }

            // 확대 방지
            if (!$this->options['enlarge'] && ($newWidth > $srcWidth || $newHeight > $srcHeight)) {
                $newWidth  = $srcWidth;
                $newHeight = $srcHeight;
            }

            // 리사이즈를 위한 임시 이미지
            $resized = \imagecreatetruecolor($newWidth, $newHeight);
            \imagealphablending($resized, false);
            \imagesavealpha($resized, true);

            // 리사이즈
            \imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

            // 최종 캔버스 생성
            $canvas = \imagecreatetruecolor($this->width, $this->height);
            \imagealphablending($canvas, false);
            \imagesavealpha($canvas, true);

            // 배경 설정
            if ('transparent' === $this->options['background']) {
                $transparent = \imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                \imagefill($canvas, 0, 0, $transparent);
            } else {
                $rgb        = $this->hexToRgb($this->options['background']);
                $background = \imagecolorallocate($canvas, $rgb[0], $rgb[1], $rgb[2]);
                \imagefill($canvas, 0, 0, $background);
            }

            // 중앙 정렬하여 합성
            \imagecopy(
                $canvas,
                $resized,
                (int) (($this->width - $newWidth) / 2),
                (int) (($this->height - $newHeight) / 2),
                0,
                0,
                $newWidth,
                $newHeight
            );

            \imagedestroy($resized);
            \imagedestroy($this->image);
            $this->image = $canvas;
        } else {
            // Crop 모드
            $scale = \max($this->width / $srcWidth, $this->height / $srcHeight);

            if (!$this->options['enlarge'] && $scale > 1) {
                $scale = 1;
            }

            $newWidth  = (int) ($srcWidth * $scale);
            $newHeight = (int) ($srcHeight * $scale);

            $resized = \imagecreatetruecolor($newWidth, $newHeight);
            \imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

            // 중앙 기준 crop을 위한 최종 이미지
            $final = \imagecreatetruecolor($this->width, $this->height);
            \imagecopy(
                $final,
                $resized,
                0,
                0,
                (int) (($newWidth - $this->width) / 2),
                (int) (($newHeight - $this->height) / 2),
                $this->width,
                $this->height
            );

            \imagedestroy($resized);
            \imagedestroy($this->image);
            $this->image = $final;
        }
    }

    public function saveToStream($stream, string $format = 'jpeg') : void
    {
        if (!$this->image) {
            throw new \RuntimeException('No image to save. Create an image first.');
        }

        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid stream resource.');
        }

        switch ($format) {
            case 'png':
            case 'webp':
                \imagealphablending($this->image, false);
                \imagesavealpha($this->image, true);

                if ('png' === $format) {
                    \imagepng($this->image, $stream, 9);
                } else {
                    \imagewebp($this->image, $stream, $this->options['quality']);
                }

                break;
            case 'gif':
                \imagegif($this->image, $stream);

                break;
            case 'jpeg':
            case 'jpg':
                \imagejpeg($this->image, $stream, $this->options['quality']);

                break;

            default:
                throw new \InvalidArgumentException("Unsupported image format: {$format}");
        }
    }

    public function getImageData(string $format = 'jpeg') : string
    {
        if (!$this->image) {
            throw new \RuntimeException('No image data. Create an image first.');
        }

        $stream = \fopen('php://temp', 'w+');
        $this->saveToStream($stream, $format);
        \rewind($stream);
        $data = \stream_get_contents($stream);
        \fclose($stream);

        if (false === $data) {
            throw new \RuntimeException('Failed to get image data from stream.');
        }

        return $data;
    }

    private function hexToRgb(string $hex) : array
    {
        $hex = \ltrim($hex, '#');

        if (3 == \strlen($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            \hexdec(\substr($hex, 0, 2)),
            \hexdec(\substr($hex, 2, 2)),
            \hexdec(\substr($hex, 4, 2)),
        ];
    }

    public function __destruct()
    {
        if ($this->image instanceof \GdImage || \is_resource($this->image)) {
            \imagedestroy($this->image);
        }
    }
}
