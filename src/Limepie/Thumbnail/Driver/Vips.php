<?php
declare(strict_types=1);

namespace Limepie\Thumbnail\Driver;

use Jcupitt\Vips\Image;
use Limepie\Thumbnail\DriverAbstract;
use Limepie\Thumbnail\DriverException;

class Vips extends DriverAbstract
{
    public function __destruct()
    {
        $this->image = null; // Vips는 자동으로 메모리를 관리합니다
    }

    public function createFromFile(string $filepath) : void
    {
        if (!\file_exists($filepath) || !\is_readable($filepath)) {
            throw new DriverException(
                "File not found or not readable: {$filepath}"
            );
        }

        try {
            $this->image = Image::newFromFile($filepath, [
                'access' => 'sequential',
            ]);

            $this->originalDimensions = [
                'width'  => $this->image->width,
                'height' => $this->image->height,
            ];

            $this->preserveTransparency();
        } catch (\throwable $e) {
            throw new DriverException(
                "Failed to process image: {$e->getMessage()}"
            );
        }
    }

    public function createFromString(string $data) : void
    {
        try {
            $this->image = Image::newFromBuffer($data);

            $this->originalDimensions = [
                'width'  => $this->image->width,
                'height' => $this->image->height,
            ];

            $this->preserveTransparency();
        } catch (\throwable $e) {
            throw new DriverException(
                "Failed to process image: {$e->getMessage()}"
            );
        }
    }

    protected function preserveTransparency() : void
    {
        try {
            // 이미지가 알파 채널을 가지고 있지 않다면 추가
            if (!$this->image->hasAlpha()) {
                $this->image = $this->image->bandjoin(255);
            }
        } catch (\throwable $e) {
            throw new DriverException(
                "Failed to set transparency: {$e->getMessage()}"
            );
        }
    }

    protected function getImageDimensions() : array
    {
        return [
            'width'  => $this->image->width,
            'height' => $this->image->height,
        ];
    }

    public function resize(int $width, int $height) : void
    {
        try {
            $dimensions = $this->calculateDimensions($width, $height, false);

            // 리사이징
            $resized = $this->image->resize(
                $dimensions['width'] / $this->originalDimensions['width'],
                [
                    'kernel' => 'lanczos3',
                    'height' => $dimensions['height'] / $this->originalDimensions['height'],
                ]
            );

            // 배경 이미지 생성
            $background = $this->createBackground($width, $height);

            // 중앙 정렬하여 합성
            $left = (int) (($width - $dimensions['width']) / 2);
            $top  = (int) (($height - $dimensions['height']) / 2);

            $this->image = $background->composite($resized, 'over', [
                'x' => $left,
                'y' => $top,
            ]);
        } catch (\throwable $e) {
            throw new DriverException("Failed to resize image: {$e->getMessage()}");
        }
    }

    public function crop(int $width, int $height) : void
    {
        try {
            $dimensions = $this->calculateDimensions($width, $height, true);

            // 리사이징
            $resized = $this->image->resize(
                $dimensions['width'] / $this->originalDimensions['width'],
                // [
                //     'kernel' => 'lanczos3',
                //     'height' => $dimensions['height'] / $this->originalDimensions['height'],
                // ]
            );
            // 중앙 기준 crop
            $left = (int) (($dimensions['width'] - $width) / 2);
            $top  = (int) (($dimensions['height'] - $height) / 2);

            $this->image = $resized->crop($left, $top, $width, $height);
        } catch (\throwable $e) {
            throw new DriverException("Failed to crop image: {$e->getMessage()} {$e->getFile()} {$e->getLine()}");
        }
    }

    private function createBackground(int $width, int $height) : Image
    {
        try {
            if ('transparent' === $this->options['background']) {
                $background = Image::black($width, $height)
                    ->bandjoin([0, 0, 0, 0])
                ;
            } elseif (\is_array($this->options['background'])
                     && 4 === \count($this->options['background'])) {
                $background = Image::black($width, $height)
                    ->bandjoin($this->options['background'])
                ;
            } else {
                throw new DriverException('Invalid background color format');
            }

            return $background;
        } catch (\throwable $e) {
            throw new DriverException(
                "Failed to create background: {$e->getMessage()}"
            );
        }
    }

    public function saveToStream($stream, string $format = 'jpeg') : void
    {
        if (!$this->image) {
            throw new DriverException('No image to save. Create an image first.');
        }

        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid stream resource.');
        }

        try {
            $options = [];

            if ('jpeg' === $format || 'jpg' === $format) {
                $options['Q'] = $this->options['quality'];
            } elseif ('png' === $format) {
                $options['compression'] = \min(9, (int) (9 * $this->options['quality'] / 100));
            }

            $data = $this->image->writeToBuffer(".{$format}", $options);

            if (false === \fwrite($stream, $data)) {
                throw new DriverException('Failed to write to stream');
            }
        } catch (\throwable $e) {
            throw new DriverException("Failed to save image: {$e->getMessage()}");
        }
    }

    public function getImageData(string $format = 'jpeg') : string
    {
        if (!$this->image) {
            throw new DriverException('No image data. Create an image first.');
        }

        try {
            $options = [];

            if ('jpeg' === $format || 'jpg' === $format) {
                $options['Q'] = $this->options['quality'];
            } elseif ('png' === $format) {
                $options['compression'] = \min(9, (int) (9 * $this->options['quality'] / 100));
            }

            return $this->image->writeToBuffer(".{$format}", $options);
        } catch (\throwable $e) {
            throw new DriverException("Failed to get image data: {$e->getMessage()}");
        }
    }
}
