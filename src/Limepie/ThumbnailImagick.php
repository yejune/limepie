<?php

declare(strict_types=1);

namespace Limepie;

class ThumbnailImagick
{
    public const SCALE_EXACT_FIT = 'crop';

    public const SCALE_SHOW_ALL = 'scale';

    private ?\Imagick $image = null; // 초기값 null로 명시적 선언

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

        $this->image = new \Imagick($filepath);

        if (!$this->image->valid()) {
            throw new \RuntimeException("Failed to create image from file: {$filepath}");
        }

        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    public function createFromString(string $data) : self
    {
        $this->image = new \Imagick();
        $this->image->readImageBlob($data);

        if (!$this->image->valid()) {
            throw new \RuntimeException('Failed to create image from string data');
        }

        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    public function createFromResource($resource) : self
    {
        if (!$resource instanceof \Imagick) {
            throw new \InvalidArgumentException('Invalid resource. Must be an Imagick object.');
        }

        $this->image = $resource;
        $this->preserveTransparency();
        $this->resizeImage();

        return $this;
    }

    private function preserveTransparency() : void
    {
        $this->image->setImageFormat('png');
        $this->image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
        $this->image->setImageBackgroundColor(new \ImagickPixel('transparent'));
    }

    // private function resizeImage() : void
    // {
    //     $srcWidth  = $this->image->getImageWidth();
    //     $srcHeight = $this->image->getImageHeight();

    //     $srcRatio = $srcWidth    / $srcHeight;
    //     $dstRatio = $this->width / $this->height;

    //     // 요청된 크기가 원본보다 큰 경우 처리
    //     if ($this->width > $srcWidth || $this->height > $srcHeight) {
    //         if (!$this->options['enlarge']) {
    //             // 확대하지 않고 원본 크기 유지
    //             $newWidth  = $srcWidth;
    //             $newHeight = $srcHeight;
    //         } else {
    //             // 확대 허용
    //             if (self::SCALE_SHOW_ALL === $this->scale) {
    //                 if ($srcRatio > $dstRatio) {
    //                     $newWidth  = $this->width;
    //                     $newHeight = (int) ($this->width / $srcRatio);
    //                 } else {
    //                     $newHeight = $this->height;
    //                     $newWidth  = (int) ($this->height * $srcRatio);
    //                 }
    //             } else {
    //                 $newWidth  = $this->width;
    //                 $newHeight = $this->height;
    //             }
    //         }
    //     } else {
    //         // 기존 로직
    //         if (self::SCALE_SHOW_ALL === $this->scale) {
    //             if ($srcRatio > $dstRatio) {
    //                 $newWidth  = $this->width;
    //                 $newHeight = (int) ($this->width / $srcRatio);
    //             } else {
    //                 $newHeight = $this->height;
    //                 $newWidth  = (int) ($this->height * $srcRatio);
    //             }
    //         } else {
    //             $newWidth  = $this->width;
    //             $newHeight = $this->height;
    //         }
    //     }

    //     $this->image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);
    //     // Create a canvas with the target dimensions
    //     $canvas = new \Imagick();

    //     try {
    //         $canvas->newImage($this->width, $this->height, new \ImagickPixel($this->options['background']));
    //     } catch (\Throwable $e) {
    //         throw new Exception($this->options['background'] . ' ' . $e->getMessage());
    //     }
    //     $canvas->setImageFormat('png');
    //     $canvas->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
    //     $canvas->compositeImage($this->image, \Imagick::COMPOSITE_OVER, (int) (($this->width - $newWidth) / 2), (int) (($this->height - $newHeight) / 2));

    //     $this->image = $canvas;
    // }

    private function resizeImage() : void
    {
        $srcWidth  = $this->image->getImageWidth();
        $srcHeight = $this->image->getImageHeight();
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

            // 확대 여부 확인
            if (!$this->options['enlarge'] && ($newWidth > $srcWidth || $newHeight > $srcHeight)) {
                $newWidth  = $srcWidth;
                $newHeight = $srcHeight;
            }

            $this->image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);

            // 배경 캔버스 생성 (요청된 크기)
            $canvas = new \Imagick();
            $canvas->newImage($this->width, $this->height, new \ImagickPixel($this->options['background']));
            $canvas->setImageFormat('png');
            $canvas->compositeImage(
                $this->image,
                \Imagick::COMPOSITE_OVER,
                (int) (($this->width - $newWidth) / 2),
                (int) (($this->height - $newHeight) / 2)
            );
            $this->image = $canvas;
        } else {
            // Crop 모드
            $scale = \max($this->width / $srcWidth, $this->height / $srcHeight);

            // 확대 여부 확인
            if (!$this->options['enlarge'] && $scale > 1) {
                $scale = 1;
            }

            $newWidth  = (int) ($srcWidth * $scale);
            $newHeight = (int) ($srcHeight * $scale);

            $this->image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1);

            // 중앙 기준 crop
            $x = (int) (($newWidth - $this->width) / 2);
            $y = (int) (($newHeight - $this->height) / 2);
            $this->image->cropImage($this->width, $this->height, $x, $y);
        }

        $this->image->setImagePage(0, 0, 0, 0);
    }

    public function saveToStream($stream, string $format = 'jpeg') : void
    {
        if (!$this->image) {
            throw new \RuntimeException('No image to save. Create an image first.');
        }

        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid stream resource.');
        }

        $this->image->setImageFormat($format);
        $this->image->setImageCompressionQuality($this->options['quality']);

        $data = $this->image->getImageBlob();
        \fwrite($stream, $data);
    }

    public function getImageData(string $format = 'jpeg') : string
    {
        if (!$this->image) {
            throw new \RuntimeException('No image data. Create an image first.');
        }

        $this->image->setImageFormat($format);
        $this->image->setImageCompressionQuality($this->options['quality']);

        return $this->image->getImageBlob();
    }

    public function __destruct()
    {
        if (null !== $this->image) {
            $this->image->clear();
            $this->image->destroy();
        }
    }
}
