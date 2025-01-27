<?php

declare(strict_types=1);

namespace Limepie;

class Thumbnail
{
    public const DRIVER_GD = 'gd';

    public const DRIVER_IMAGICK = 'imagick';

    public const DRIVER_VIPS = 'vips';

    public const SCALE_EXACT_FIT = 'crop';

    public const SCALE_SHOW_ALL = 'scale';

    private Thumbnail\DriverInterface $driver;

    private string $scale;

    private array $options;

    /**
     * Thumbnail 클래스 생성자.
     *
     * @param string $driver  사용할 드라이버 (gd, imagick, vips)
     * @param string $scale   스케일링 모드 (crop, scale)
     * @param array  $options 추가 옵션
     *
     * @throws \InvalidArgumentException 잘못된 드라이버나 옵션 지정 시
     */
    public function __construct(
        string $driver = self::DRIVER_GD,
        string $scale = self::SCALE_SHOW_ALL,
        array $options = []
    ) {
        if (!\in_array($scale, [self::SCALE_EXACT_FIT, self::SCALE_SHOW_ALL])) {
            throw new \InvalidArgumentException("Invalid scale mode: {$scale}");
        }

        $this->scale   = $scale;
        $this->options = $this->validateOptions($options);
        $this->driver  = $this->createDriver($driver);
    }

    /**
     * 옵션 값들을 검증하고 기본값과 병합.
     *
     * @param array $options 사용자 지정 옵션
     *
     * @return array 검증된 옵션
     *
     * @throws \InvalidArgumentException 잘못된 옵션 값
     */
    private function validateOptions(array $options) : array
    {
        $defaults = [
            'background' => 'transparent',
            'quality'    => 90,
            'enlarge'    => true,
        ];

        $options = \array_merge($defaults, $options);

        if ($options['quality'] < 0 || $options['quality'] > 100) {
            throw new \InvalidArgumentException(
                'Quality must be between 0 and 100'
            );
        }

        if (!\is_bool($options['enlarge'])) {
            throw new \InvalidArgumentException(
                'Enlarge option must be boolean'
            );
        }

        if (!\is_string($options['background']) && !$this->isValidRgbaArray($options['background'])) {
            throw new \InvalidArgumentException(
                'Background must be "transparent" or an RGBA array with values 0-255'
            );
        }

        return $options;
    }

    private function isValidRgbaArray($array) : bool
    {
        if (!\is_array($array) || 4 !== \count($array)) {
            return false;
        }

        foreach ($array as $value) {
            if (!\is_int($value) || $value < 0 || $value > 255) {
                return false;
            }
        }

        return true;
    }

    /**
     * 지정된 드라이버의 인스턴스를 생성.
     *
     * @param string $driver 드라이버 이름
     *
     * @return DriverInterface 드라이버 인스턴스
     *
     * @throws \InvalidArgumentException 지원하지 않는 드라이버
     * @throws \RuntimeException         드라이버 생성 실패
     */
    private function createDriver(string $driver) : Thumbnail\DriverInterface
    {
        // 드라이버 지원 여부 확인
        $supported = match ($driver) {
            self::DRIVER_GD      => \extension_loaded('gd'),
            self::DRIVER_IMAGICK => \extension_loaded('imagick'),
            self::DRIVER_VIPS    => \extension_loaded('vips'),
            default              => false
        };

        if (!$supported) {
            throw new \RuntimeException(
                "Driver '{$driver}' is not available. Please install the required extension."
            );
        }

        // 드라이버 인스턴스 생성
        return match ($driver) {
            self::DRIVER_GD      => new Thumbnail\Driver\Gd($this->options),
            self::DRIVER_IMAGICK => new Thumbnail\Driver\Imagick($this->options),
            self::DRIVER_VIPS    => new Thumbnail\Driver\Vips($this->options),
            default              => throw new \InvalidArgumentException("Unsupported driver: {$driver}")
        };
    }

    /**
     * 이미지 파일을 처리.
     *
     * @param string $source 파일 경로 또는 이미지 데이터
     * @param int    $width  목표 너비
     * @param int    $height 목표 높이
     * @param string $type   소스 타입 ('file' 또는 'string')
     *
     * @throws \InvalidArgumentException 잘못된 파라미터
     * @throws ThumbnailException        처리 실패
     */
    public function process(
        string $source,
        int $width,
        int $height,
        string $type = 'file'
    ) : self {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException(
                'Invalid dimensions. Width and height must be positive.'
            );
        }

        if (!\in_array($type, ['file', 'string'])) {
            throw new \InvalidArgumentException(
                "Invalid source type. Must be 'file' or 'string'."
            );
        }

        try {
            if ('file' === $type) {
                $this->driver->createFromFile($source);
            } else {
                $this->driver->createFromString($source);
            }

            if (self::SCALE_EXACT_FIT === $this->scale) {
                $this->driver->crop($width, $height);
            } else {
                $this->driver->resize($width, $height);
            }

            return $this;
        } catch (Thumbnail\Driver\DriverException $e) {
            throw new Thumbnail\Driver\ThumbnailException(
                "Failed to process image: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * 처리된 이미지를 스트림에 저장.
     *
     * @param resource $stream 출력 스트림
     * @param string   $format 이미지 포맷
     *
     * @throws ThumbnailException 저장 실패
     */
    public function saveToStream($stream, string $format = 'jpeg') : void
    {
        try {
            $this->driver->saveToStream($stream, $format);
        } catch (Thumbnail\Driver\DriverException $e) {
            throw new Thumbnail\Driver\ThumbnailException(
                "Failed to save image: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * 처리된 이미지 데이터를 문자열로 반환.
     *
     * @param string $format 이미지 포맷
     *
     * @return string 이미지 데이터
     *
     * @throws ThumbnailException 변환 실패
     */
    public function getImageData(string $format = 'jpeg') : string
    {
        try {
            return $this->driver->getImageData($format);
        } catch (Thumbnail\Driver\DriverException $e) {
            throw new Thumbnail\Driver\ThumbnailException(
                "Failed to get image data: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * 사용 가능한 드라이버 목록을 반환.
     *
     * @return array 사용 가능한 드라이버 목록
     */
    public static function getAvailableDrivers() : array
    {
        $drivers = [];

        if (\extension_loaded('gd')) {
            $drivers[] = self::DRIVER_GD;
        }

        if (\extension_loaded('imagick')) {
            $drivers[] = self::DRIVER_IMAGICK;
        }

        if (\extension_loaded('vips')) {
            $drivers[] = self::DRIVER_VIPS;
        }

        return $drivers;
    }
}
