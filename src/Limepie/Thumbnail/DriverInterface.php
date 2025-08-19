<?php

declare(strict_types=1);

namespace Limepie\Thumbnail;

// src/Exception/ThumbnailException.php
class ThumbnailException extends \RuntimeException {}

// src/Exception/DriverException.php
class DriverException extends ThumbnailException {}

// src/DriverInterface.php
interface DriverInterface
{
    /**
     * 파일로부터 이미지를 생성합니다.
     *
     * @param string $filepath 이미지 파일 경로
     *
     * @throws DriverException 파일 로드 실패 시
     */
    public function createFromFile(string $filepath) : void;

    /**
     * 문자열 데이터로부터 이미지를 생성합니다.
     *
     * @param string $data 이미지 데이터
     *
     * @throws DriverException 이미지 생성 실패 시
     */
    public function createFromString(string $data) : void;

    /**
     * 비율을 유지하며 이미지를 리사이즈합니다.
     *
     * @param int $width  목표 너비
     * @param int $height 목표 높이
     *
     * @throws DriverException 리사이징 실패 시
     */
    public function resize(int $width, int $height) : void;

    /**
     * 지정된 크기로 이미지를 크롭합니다.
     *
     * @param int $width  목표 너비
     * @param int $height 목표 높이
     *
     * @throws DriverException 크롭 실패 시
     */
    public function crop(int $width, int $height) : void;

    /**
     * 이미지를 스트림에 저장합니다.
     *
     * @param resource $stream 출력 스트림
     * @param string   $format 이미지 포맷 (jpeg, png, gif)
     *
     * @throws DriverException 저장 실패 시
     */
    public function saveToStream($stream, string $format = 'jpeg') : void;

    /**
     * 이미지 데이터를 문자열로 반환합니다.
     *
     * @param string $format 이미지 포맷 (jpeg, png, gif)
     *
     * @return string 이미지 데이터
     *
     * @throws DriverException 변환 실패 시
     */
    public function getImageData(string $format = 'jpeg') : string;
}
