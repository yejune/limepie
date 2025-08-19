<?php
declare(strict_types=1);

namespace Limepie\Thumbnail\Driver;

use Limepie\Thumbnail\DriverAbstract;
use Limepie\Thumbnail\DriverException;

// src/Driver/ImagickDriver.php
class Imagick extends DriverAbstract
{
    public function __destruct()
    {
        if ($this->image instanceof \Imagick) {
            $this->image->clear();
            $this->image->destroy();
        }
    }

    public function createFromFile(string $filepath) : void
    {
        if (!\file_exists($filepath) || !\is_readable($filepath)) {
            throw new DriverException(
                "File not found or not readable: {$filepath}"
            );
        }

        try {
            $this->image = new \Imagick($filepath);

            if (!$this->image->valid()) {
                throw new DriverException('Failed to create image from file');
            }

            $this->originalDimensions = [
                'width'  => $this->image->getImageWidth(),
                'height' => $this->image->getImageHeight(),
            ];

            $this->preserveTransparency();
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to process image: {$e->getMessage()}");
        }
    }

    public function createFromString(string $data) : void
    {
        try {
            $this->image = new \Imagick();
            $this->image->readImageBlob($data);

            if (!$this->image->valid()) {
                throw new DriverException('Failed to create image from string data');
            }

            $this->originalDimensions = [
                'width'  => $this->image->getImageWidth(),
                'height' => $this->image->getImageHeight(),
            ];

            $this->preserveTransparency();
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to process image: {$e->getMessage()}");
        }
    }

    protected function preserveTransparency() : void
    {
        try {
            $this->image->setImageFormat('png');
            $this->image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

            if ('transparent' === $this->options['background']) {
                $this->image->setImageBackgroundColor(new \ImagickPixel('transparent'));
            } elseif (\is_array($this->options['background'])) {
                $rgba = \sprintf(
                    'rgba(%d,%d,%d,%f)',
                    $this->options['background'][0],
                    $this->options['background'][1],
                    $this->options['background'][2],
                    $this->options['background'][3] / 255
                );
                $this->image->setImageBackgroundColor(new \ImagickPixel($rgba));
            } else {
                throw new DriverException('Invalid background color format');
            }
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to set transparency: {$e->getMessage()}");
        }
    }

    protected function getImageDimensions() : array
    {
        try {
            return [
                'width'  => $this->image->getImageWidth(),
                'height' => $this->image->getImageHeight(),
            ];
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to get image dimensions: {$e->getMessage()}");
        }
    }

    public function resize(int $width, int $height) : void
    {
        try {
            $dimensions = $this->calculateDimensions($width, $height, false);

            // 새 캔버스 생성
            $canvas = new \Imagick();
            $canvas->newImage(
                $width,
                $height,
                new \ImagickPixel($this->getBackgroundColor())
            );
            $canvas->setImageFormat('png');

            // 이미지 리사이징
            $this->image->resizeImage(
                $dimensions['width'],
                $dimensions['height'],
                \Imagick::FILTER_LANCZOS,
                1
            );

            // 중앙 정렬하여 합성
            $left = (int) (($width - $dimensions['width']) / 2);
            $top  = (int) (($height - $dimensions['height']) / 2);

            $canvas->compositeImage(
                $this->image,
                \Imagick::COMPOSITE_OVER,
                $left,
                $top
            );

            $this->image = $canvas;
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to resize image: {$e->getMessage()}");
        }
    }

    public function crop(int $width, int $height) : void
    {
        try {
            $dimensions = $this->calculateDimensions($width, $height, true);

            // 이미지 리사이징
            $this->image->resizeImage(
                $dimensions['width'],
                $dimensions['height'],
                \Imagick::FILTER_LANCZOS,
                1
            );

            // 중앙 기준 crop
            $left = (int) (($dimensions['width'] - $width) / 2);
            $top  = (int) (($dimensions['height'] - $height) / 2);

            $this->image->cropImage($width, $height, $left, $top);
            $this->image->setImagePage(0, 0, 0, 0);
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to crop image: {$e->getMessage()}");
        }
    }

    private function getBackgroundColor() : string
    {
        if ('transparent' === $this->options['background']) {
            return 'transparent';
        }

        if (\is_array($this->options['background'])
            && 4 === \count($this->options['background'])) {
            return \sprintf(
                'rgba(%d,%d,%d,%f)',
                $this->options['background'][0],
                $this->options['background'][1],
                $this->options['background'][2],
                $this->options['background'][3] / 255
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

        try {
            $this->image->setImageFormat($format);

            if ('jpeg' === $format || 'jpg' === $format) {
                $this->image->setImageCompressionQuality($this->options['quality']);
            } elseif ('png' === $format) {
                $this->image->setImageCompressionQuality(
                    \min(9, (int) (9 * $this->options['quality'] / 100)) * 10
                );
            }

            $data = $this->image->getImageBlob();

            if (false === \fwrite($stream, $data)) {
                throw new DriverException('Failed to write to stream');
            }
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to save image: {$e->getMessage()}");
        }
    }

    public function getImageData(string $format = 'jpeg') : string
    {
        if (!$this->image) {
            throw new DriverException('No image data. Create an image first.');
        }

        try {
            $this->image->setImageFormat($format);

            if ('jpeg' === $format || 'jpg' === $format) {
                $this->image->setImageCompressionQuality($this->options['quality']);
            } elseif ('png' === $format) {
                $this->image->setImageCompressionQuality(
                    \min(9, (int) (9 * $this->options['quality'] / 100)) * 10
                );
            }

            return $this->image->getImageBlob();
        } catch (\ImagickException $e) {
            throw new DriverException("Failed to get image data: {$e->getMessage()}");
        }
    }
}
