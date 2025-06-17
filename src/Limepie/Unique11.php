<?php declare(strict_types=1);

namespace Limepie;

class Unique11
{
    // GMP 네이티브 Base62 문자셋
    private const GMP_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    // 커스텀 문자셋
    private const CUSTOM_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private static int $lastTimestamp = 0;

    private static int $sequence = 0;

    private static ?int $machineId = null;

    /**
     * 최적화된 11자리 유니크 ID 생성.
     */
    public static function generate() : string
    {
        $timestamp = (int) (\microtime(true) * 1000); // 밀리초

        // 시퀀스 관리 (동일 밀리초 처리)
        if ($timestamp === self::$lastTimestamp) {
            ++self::$sequence;

            // 시퀀스 오버플로우 처리 (16비트 = 65535)
            if (self::$sequence > 0xFFFF) {
                while ($timestamp <= self::$lastTimestamp) {
                    \usleep(100); // 0.1ms 대기
                    $timestamp = (int) (\microtime(true) * 1000);
                }
                self::$sequence = 0;
            }
        } else {
            self::$sequence = 0;
        }

        self::$lastTimestamp = $timestamp;

        // 머신 ID 초기화 (한 번만)
        if (null === self::$machineId) {
            self::$machineId = self::generateMachineId();
        }

        // 64비트 ID 조합: [40비트 타임스탬프][16비트 시퀀스][8비트 머신ID]
        $combined = ($timestamp << 24) | (self::$sequence << 8) | self::$machineId;

        return self::encodeInteger($combined);
    }

    /**
     * 정수를 Base62로 인코딩 (GMP 최적화).
     */
    private static function encodeInteger(int $data) : string
    {
        if (!\extension_loaded('gmp')) {
            return self::encodeIntegerPHP($data);
        }

        // GMP 네이티브 Base62 인코딩 (매우 빠름!)
        $base62 = \gmp_strval(\gmp_init($data, 10), 62);

        // 11자리로 패딩 (리딩 제로 처리)
        $padded = \str_pad($base62, 11, self::GMP_CHARSET[0], STR_PAD_LEFT);

        // 커스텀 문자셋 사용시 변환
        if (self::GMP_CHARSET !== self::CUSTOM_CHARSET) {
            return \strtr($padded, self::GMP_CHARSET, self::CUSTOM_CHARSET);
        }

        return $padded;
    }

    /**
     * Base62 디코딩 (ID 파싱용).
     */
    public static function decodeInteger(string $data) : int
    {
        self::validateInput($data);

        if (!\extension_loaded('gmp')) {
            return self::decodeIntegerPHP($data);
        }

        // 커스텀 문자셋에서 GMP 문자셋으로 변환
        if (self::GMP_CHARSET !== self::CUSTOM_CHARSET) {
            $data = \strtr($data, self::CUSTOM_CHARSET, self::GMP_CHARSET);
        }

        // GMP로 디코딩
        return (int) \gmp_strval(\gmp_init($data, 62), 10);
    }

    /**
     * ID 정보 파싱 (디버깅 및 분석용).
     */
    public static function parseId(string $id) : array
    {
        $num = self::decodeInteger($id);

        $machineId = $num        & 0xFF;
        $sequence  = ($num >> 8) & 0xFFFF;
        $timestamp = $num >> 24;

        return [
            'id'         => $id,
            'timestamp'  => $timestamp,
            'datetime'   => \date('Y-m-d H:i:s.v', $timestamp / 1000),
            'sequence'   => $sequence,
            'machine_id' => $machineId,
            'raw_number' => $num,
            'binary'     => \sprintf('0b%064b', $num),
            'hex'        => \sprintf('0x%016X', $num),
        ];
    }

    /**
     * 머신 ID 생성 (8비트 = 0-255).
     */
    private static function generateMachineId() : int
    {
        $factors = [
            \php_uname('n'),                    // 호스트명
            $_SERVER['SERVER_ADDR'] ?? '127.0.0.1', // 서버 IP
            (string) \getmypid(),                // 프로세스 ID
            (string) \php_uname('m'),            // 머신 타입
            (string) \time(),                     // 시작 시간 (재시작 시 변경)
        ];

        $identifier = \implode('|', $factors);

        return \crc32($identifier) & 0xFF; // 8비트
    }

    /**
     * 입력 검증 (Tuupola 방식 적용).
     */
    private static function validateInput(string $data) : void
    {
        $charset = self::CUSTOM_CHARSET;

        // 허용된 문자셋에 없는 문자 확인
        if (\strlen($data) !== \strspn($data, $charset)) {
            $valid   = \str_split($charset);
            $invalid = \str_replace($valid, '', $data);
            $invalid = \count_chars($invalid, 3);

            throw new InvalidArgumentException(
                "ID contains invalid characters: \"{$invalid}\""
            );
        }

        // 길이 검증
        if (11 !== \strlen($data)) {
            throw new InvalidArgumentException(
                'ID must be exactly 11 characters long, got ' . \strlen($data)
            );
        }
    }

    /**
     * 순수 PHP 백업 인코더 (GMP 없을 때).
     */
    private static function encodeIntegerPHP(int $data) : string
    {
        $chars = self::CUSTOM_CHARSET;

        if (0 === $data) {
            return \str_repeat($chars[0], 11);
        }

        $result = '';

        while ($data > 0) {
            $result = $chars[$data % 62] . $result;
            $data   = (int) ($data / 62);
        }

        return \str_pad($result, 11, $chars[0], STR_PAD_LEFT);
    }

    /**
     * 순수 PHP 백업 디코더 (GMP 없을 때).
     */
    private static function decodeIntegerPHP(string $data) : int
    {
        $chars   = self::CUSTOM_CHARSET;
        $charMap = \array_flip(\str_split($chars));

        $result = 0;
        $length = \strlen($data);

        for ($i = 0; $i < $length; ++$i) {
            $result = $result * 62 + $charMap[$data[$i]];
        }

        return $result;
    }

    /**
     * 시스템 정보 반환.
     */
    public static function getSystemInfo() : array
    {
        return [
            'gmp_available'  => \extension_loaded('gmp'),
            'machine_id'     => self::$machineId ?? 'not_initialized',
            'charset'        => self::CUSTOM_CHARSET,
            'last_timestamp' => self::$lastTimestamp,
            'sequence'       => self::$sequence,
            'php_version'    => PHP_VERSION,
            'max_sequence'   => 0xFFFF,
            'max_machine_id' => 0xFF,
        ];
    }
}
