<?php

declare(strict_types=1);

namespace Limepie\Thumbnail;

// src/AbstractDriver.php
abstract class DriverAbstract implements DriverInterface
{
    /**
     * 이미지 리소스/객체.
     */
    public $image;

    /**
     * 드라이버 설정.
     */
    protected array $options;

    /**
     * 원본 이미지 크기.
     */
    protected array $originalDimensions = ['width' => 0, 'height' => 0];

    /**
     * @param array $options 드라이버 설정
     */
    public function __construct(array $options = [])
    {
        $this->options = \array_merge([
            'background' => 'transparent',
            'quality'    => 90,
            'enlarge'    => true,
        ], $options);

        $this->validateOptions();
    }

    /**
     * 옵션 값들을 검증합니다.
     *
     * @throws \RuntimeException 잘못된 옵션 값
     */
    protected function validateOptions() : void
    {
        if ($this->options['quality'] < 0 || $this->options['quality'] > 100) {
            throw new ThumbnailException('Quality must be between 0 and 100');
        }

        if (!\is_bool($this->options['enlarge'])) {
            throw new ThumbnailException('Enlarge option must be boolean');
        }
    }

    /**
     * 투명도 설정을 처리합니다.
     */
    abstract protected function preserveTransparency() : void;

    /**
     * 현재 이미지의 크기를 반환합니다.
     *
     * @return array ['width' => int, 'height' => int]
     */
    abstract protected function getImageDimensions() : array;

    /**
     * 새로운 크기를 계산합니다.
     *
     * @param int  $targetWidth  목표 너비
     * @param int  $targetHeight 목표 높이
     * @param bool $crop         크롭 모드 여부
     *
     * @return array ['width' => int, 'height' => int]
     */
    protected function calculateDimensions(
        int $targetWidth,
        int $targetHeight,
        bool $crop = false
    ) : array {
        $srcWidth    = $this->originalDimensions['width'];
        $srcHeight   = $this->originalDimensions['height'];
        $srcRatio    = $srcWidth    / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($crop) {
            $scale = \max($targetWidth / $srcWidth, $targetHeight / $srcHeight);

            if (!$this->options['enlarge'] && $scale > 1) {
                $scale = 1;
            }

            $newWidth  = (int) ($srcWidth * $scale);
            $newHeight = (int) ($srcHeight * $scale);
        } else {
            if ($srcRatio > $targetRatio) {
                $newWidth  = $targetWidth;
                $newHeight = (int) ($targetWidth / $srcRatio);
            } else {
                $newHeight = $targetHeight;
                $newWidth  = (int) ($targetHeight * $srcRatio);
            }

            if (!$this->options['enlarge']
                && ($newWidth > $srcWidth || $newHeight > $srcHeight)) {
                $newWidth  = $srcWidth;
                $newHeight = $srcHeight;
            }
        }

        return ['width' => $newWidth, 'height' => $newHeight];
    }
}
