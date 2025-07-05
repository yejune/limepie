<?php

declare(strict_types=1);

namespace Limepie;

use Limepie\RecursiveIterator\AdjacencyList;

function _($string)
{
    return \dgettext('system', $string);
}
function __($domain, $string)
{
    if ($string) {
        return \dgettext($domain, $string);
    }

    return '';
}
function ___($domain, $string, $a, $b)
{
    return \dngettext($domain, $string, $a, $b);
}

function lang(array $data, string $key, string $language = 'ko')
{
    return $data[$key . '_langs'][$language] ?? $data[$key] ?? '';
}

/**
 * Google reCAPTCHA 토큰을 검증합니다.
 *
 * @param string      $token            클라이언트로부터 받은 g-recaptcha-response
 * @param string      $secretKey        reCAPTCHA 비밀 키
 * @param null|string $userIp           사용자의 IP (선택사항)
 * @param int         $timeoutSeconds   토큰 유효 시간(초), 기본 120초
 * @param null|string $expectedHostname 예상 호스트명 (선택사항)
 *
 * @return array ['success' => bool, 'errors' => array]
 */
function verify_recaptcha(
    string $token,
    string $secretKey,
    ?string $userIp = null,
    int $timeoutSeconds = 120,
    ?string $expectedHostname = null
) : array {
    // 1) 토큰 유무 확인
    if ('' === \trim($token)) {
        return ['success' => false, 'errors' => ['missing-input-response']];
    }

    // 2) siteverify 호출용 파라미터 준비
    $postData = [
        'secret'   => $secretKey,
        'response' => $token,
    ];

    if ($userIp) {
        $postData['remoteip'] = $userIp;
    }

    // 3) cURL 요청
    $ch = \curl_init('https://www.google.com/recaptcha/api/siteverify');
    \curl_setopt($ch, CURLOPT_POST, true);
    \curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = \curl_exec($ch);

    \curl_close($ch);

    // 4) JSON 디코딩
    $result  = \json_decode($response, true);
    $success = $result['success']     ?? false;
    $errors  = $result['error-codes'] ?? [];

    // 5) 추가 옵션 검증: 타임아웃
    if ($success && isset($result['challenge_ts'])) {
        $ts = \strtotime($result['challenge_ts']);

        if (false === $ts || (\time() - $ts) > $timeoutSeconds) {
            $success  = false;
            $errors[] = 'timeout-or-duplicate';
        }
    }

    // 6) 추가 옵션 검증: hostname
    if ($success && $expectedHostname && isset($result['hostname'])) {
        if ($result['hostname'] !== $expectedHostname) {
            $success  = false;
            $errors[] = 'hostname-mismatch';
        }
    }

    return [
        'success' => (bool) $success,
        'errors'  => $errors,
    ];
}

/**
 * BigInt → UUID 토큰 인코딩 (CRC32×2 기반 다변화 마스크).
 *
 * Payload 구성:
 *  • nonce(4B, 평문)
 *  • time(4B, big-endian)
 *  • bigint(8B, 64-bit native)
 *
 * 뒤 12바이트(time+bigint)에만 CRC32(key . nonce)와 CRC32(nonce . key)
 * 그리고 이 둘의 XOR 결과로 생성한 12바이트 마스크를 XOR 처리합니다.
 *
 * @param int|string $bigint 원본 정수
 * @param string     $key    최소 8바이트 이상의 비밀 키
 *
 * @return string UUID 형식 토큰
 *
 * @throws \InvalidArgumentException
 */
function int_to_uuid(int|string $bigint, string $key) : string
{
    if (\strlen($key) < 8) {
        throw new \InvalidArgumentException('Key must be at least 8 bytes.');
    }

    // 1) nonce 4바이트 (평문)
    $nonce = \random_bytes(4);
    // 2) 발급 시각(초 단위) 4바이트 BE
    $timePack = \pack('N', \time());
    // 3) BigInt 8바이트 native
    $dataPack = \pack('J', (int) $bigint);

    // 4) payload plain 12바이트 = timePack(4) │ dataPack(8)
    $plain12 = $timePack . $dataPack;

    // 5) CRC32 기반 시드 생성
    $seed1 = \crc32($key . $nonce);
    $seed2 = \crc32($nonce . $key);
    $seed3 = $seed1 ^ $seed2;

    // 6) 12바이트 마스크 생성
    $mask = \pack('N', $seed1)
          . \pack('N', $seed2)
          . \pack('N', $seed3);

    // 7) XOR 암호화 → 12바이트 cipher
    $cipher12 = $plain12 ^ $mask;

    // 8) 최종 토큰 바이너리 = nonce(4) || cipher12(12)
    $bin = $nonce . $cipher12;

    // 9) UUID 포맷 변환 (16B → 32 hex → 8-4-4-4-12)
    $hex             = \bin2hex($bin);
    [$a,$b,$c,$d,$e] = \sscanf($hex, '%8s%4s%4s%4s%12s');

    return \implode('-', [$a, $b, $c, $d, $e]);
}

/**
 * UUID 토큰 → BigInt 복호화 (만료 검증 포함).
 *
 * @param string $uuid UUID 형식 토큰
 * @param string $key  인코딩 때 사용한 동일 키
 * @param int    $ttl  유효기간(초), 0 이면 만료 검사 생략
 *
 * @return int 원본 BigInt
 *
 * @throws \InvalidArgumentException
 */
function uuid_to_int(string $uuid, string $key, int $ttl) : int
{
    // 1) 하이픈 제거 & hex→bin
    $clean = \str_replace('-', '', $uuid);

    if (32 !== \strlen($clean) || false === ($bin = \hex2bin($clean))) {
        throw new \InvalidArgumentException('Invalid token format.');
    }

    if (\strlen($key) < 8) {
        throw new \InvalidArgumentException('Key must be at least 8 bytes.');
    }

    if ($ttl < 0) {
        throw new \InvalidArgumentException('TTL must be non-negative.');
    }

    // 2) nonce(4B) 추출
    $nonce = \substr($bin, 0, 4);
    // 3) 동일 마스크 재생성
    $seed1 = \crc32($key . $nonce);
    $seed2 = \crc32($nonce . $key);
    $seed3 = $seed1 ^ $seed2;
    $mask  = \pack('N', $seed1)
              . \pack('N', $seed2)
              . \pack('N', $seed3);

    // 4) cipher12 복원 → plain12
    $cipher12 = \substr($bin, 4, 12);
    $plain12  = $cipher12 ^ $mask;

    // 5) plain12 분리: time(4B) │ bigint(8B)
    $issued = \unpack('N', \substr($plain12, 0, 4))[1];
    $bigint = \unpack('J', \substr($plain12, 4, 8))[1];

    // 6) 만료 검사
    if ($ttl > 0 && \time() > ($issued + $ttl)) {
        throw new \InvalidArgumentException('Token has expired.');
    }

    return $bigint;
}

/**
 * UUID 토큰에서 발급 시각(초 단위)만 추출.
 *
 * @param string $uuid UUID 토큰
 * @param string $key  동일 키
 *
 * @return int Unix epoch seconds
 *
 * @throws \InvalidArgumentException
 */
function uuid_get_issued_time(string $uuid, string $key) : int
{
    $clean = \str_replace('-', '', $uuid);
    $bin   = \hex2bin($clean) ?: throw new \InvalidArgumentException('Invalid token format.');

    if (\strlen($key) < 8) {
        throw new \InvalidArgumentException('Key must be at least 8 bytes.');
    }

    $nonce = \substr($bin, 0, 4);
    $seed1 = \crc32($key . $nonce);
    $seed2 = \crc32($nonce . $key);
    $seed3 = $seed1 ^ $seed2;
    $mask  = \pack('N', $seed1)
             . \pack('N', $seed2)
             . \pack('N', $seed3);

    $plain12 = \substr($bin, 4, 12) ^ $mask;

    return \unpack('N', \substr($plain12, 0, 4))[1];
}

/**
 * TTL 기준으로 만료 여부만 체크.
 *
 * @param string $uuid UUID 토큰
 * @param string $key  동일 키
 * @param int    $ttl  유효기간(초)
 *
 * @return bool true=만료됨, false=유효
 *
 * @throws \InvalidArgumentException
 */
function uuid_is_expired(string $uuid, string $key, int $ttl) : bool
{
    $issued = uuid_get_issued_time($uuid, $key);

    return $ttl > 0 && (\time() > ($issued + $ttl));
}

function uuid2int(string $uuid, string $key, int $ttl = 3600) : int
{
    return uuid_to_int($uuid, $key, $ttl);
}
function int2uuid(int|string $bigint, string $key) : string
{
    return int_to_uuid($bigint, $key);
}

/**
 * 숫자를 최소 20 이상으로, 20 초과시 10단위로 올림하여 반환.
 *
 * 1~20: 20 반환
 * 21 이후: 10단위로 올림 (21->30, 25->30, 31->40)
 *
 * @param mixed $level
 *
 * @return int 올림 처리된 숫자
 *
 * @example referral_limit(15) // 20
 * @example referral_limit(25) // 30
 * @example referral_limit(31) // 40
 */
function referral_limit($level)
{
    // max(20, ...)로 최소값 20 보장
    // ceil($number / 10) * 10으로 10단위 올림
    return \max(20, ceil($level / 10) * 10);
}

/**
 * Base62 디코딩.
 *
 * @param mixed $str
 */
function base62_decode($str)
{
    $chars   = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $charMap = \array_flip(\str_split($chars));

    $num = 0;

    for ($i = 0; $i < \strlen($str); ++$i) {
        $num = $num * 62 + $charMap[$str[$i]];
    }

    return $num;
}
/**
 * Base62 인코딩.
 *
 * @param mixed $num
 */
function base62_encode($num)
{
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    if (0 == $num) {
        return \str_repeat('0', 11);
    }

    $result = '';

    while ($num > 0) {
        $result = $chars[$num % 62] . $result;
        $num    = (int) ($num / 62);
    }

    return \str_pad($result, 11, '0', STR_PAD_LEFT);
}
/**
 * Base64 패딩 제거 함수
 * URL-safe Base64로 변환하고 패딩(=) 제거.
 *
 * @param mixed $data
 */
function base64_url_encode($data)
{
    return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 패딩 복원 및 디코딩 함수
 * 패딩을 복원하고 URL-safe Base64를 일반 Base64로 변환 후 디코딩.
 *
 * @param mixed $data
 */
function base64_url_decode($data)
{
    // URL-safe 문자를 일반 Base64 문자로 변환
    $base64 = \strtr($data, '-_', '+/');

    // 패딩 복원
    $padLength = 4 - (\strlen($base64) % 4);

    if (4 !== $padLength) {
        $base64 .= \str_repeat('=', $padLength);
    }

    return \base64_decode($base64);
}

/**
 * UUID를 압축된 Base64로 변환.
 *
 * @param mixed $uuid
 */
function compress_uuid($uuid)
{
    // 하이픈 제거
    $hex = \str_replace('-', '', $uuid);

    // 16진수를 바이너리로 변환
    $binary = \hex2bin($hex);

    // Base64 인코딩 후 패딩 제거
    return base64_url_encode($binary);
}

/**
 * 압축된 Base64를 UUID로 복원.
 *
 * @param mixed $compressed
 */
function decompress_uuid($compressed)
{
    // Base64 디코딩
    $binary = base64_url_decode($compressed);

    // 바이너리를 16진수로 변환
    $hex = \bin2hex($binary);

    // UUID 형식으로 변환
    return \sprintf(
        '%s-%s-%s-%s-%s',
        \substr($hex, 0, 8),
        \substr($hex, 8, 4),
        \substr($hex, 12, 4),
        \substr($hex, 16, 4),
        \substr($hex, 20, 12)
    );
}

/**
 * 표준 Base64 패딩만 제거 (URL-safe 변환 없이).
 *
 * @param mixed $base64
 */
function removeBase64Padding($base64)
{
    return \rtrim($base64, '=');
}

/**
 * Base64 패딩 복원 (URL-safe 변환 없이).
 *
 * @param mixed $base64
 */
function restoreBase64Padding($base64)
{
    $padLength = 4 - (\strlen($base64) % 4);

    if (4 !== $padLength) {
        $base64 .= \str_repeat('=', $padLength);
    }

    return $base64;
}

/**
 * 특정 선택지의 득표율(%)을 계산.
 *
 * @param float|int $choiceVotes 해당 선택지의 투표 수
 * @param float|int $totalVotes  전체 투표 수
 * @param int       $precision   소수점 자리수 (기본: 1)
 *
 * @return float 퍼센트 (0~100)
 */
function get_percent($choiceVotes, $totalVotes, $precision = 1)
{
    if ($totalVotes > 0) {
        return \round(($choiceVotes / $totalVotes) * 100, $precision);
    }

    return 0;
}

function validate_csrf_token($token)
{
    // 세션 확인
    if (\PHP_SESSION_ACTIVE !== \session_status()) {
        return false;
    }

    // 기본 검증
    if (empty($token)
        || empty($_SESSION['csrf_token'])
        || empty($_SESSION['csrf_token_expiry'])) {
        return false;
    }

    // 만료 시간 확인
    if (\time() > $_SESSION['csrf_token_expiry']) {
        return false;
    }

    // 토큰 비교
    return $_SESSION['csrf_token'] == $token;
}

/**
 * CSRF 토큰 강제 새로 생성 (기존 토큰 무시).
 *
 * @param int $expiry 토큰 만료 시간(초), 기본값: 3600초(1시간)
 *
 * @return string 새로운 CSRF 토큰
 */
function regenerate_csrf_token(int $expiry = 3600) : string
{
    // 세션이 시작되지 않았다면 시작
    if (\PHP_SESSION_NONE === \session_status()) {
        \session_start();
    }

    if (empty($_SESSION['csrf_token']) || \time() > $_SESSION['csrf_token_expiry']) {
        $_SESSION['csrf_token']        = \bin2hex(\random_bytes(32));
        $_SESSION['csrf_token_expiry'] = \time() + 3600; // 삭제하지 않아도 1시간만 유효
    }

    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 완전 삭제.
 *
 * @return bool 삭제 성공 여부
 */
function remove_csrf_token() : bool
{
    if (\PHP_SESSION_ACTIVE !== \session_status()) {
        return false;
    }

    // 세션에서 CSRF 토큰 관련 데이터 모두 제거
    unset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']);

    return true;
}

// 확장자를 제외한 파일명을 length만큼 잘라줌
function truncate_filename($filename, $maxLength = 200)
{
    $ext        = \pathinfo($filename, PATHINFO_EXTENSION);
    $extWithDot = $ext ? '.' . $ext : '';
    $name       = \mb_substr(\pathinfo($filename, PATHINFO_FILENAME), 0, $maxLength - \mb_strlen($extWithDot));

    return $name . $extWithDot;
}

function gen_handle(int $length = 15) : string
{
    $chars    = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $maxIndex = \strlen($chars) - 1;
    $id       = '';

    for ($i = 0; $i < $length; ++$i) {
        $id .= $chars[\random_int(0, $maxIndex)];
    }

    return $id;
}

function str_uuid($string)
{
    $md5 = \md5((string) $string);

    // UUID 형식으로 포맷팅 (8-4-4-4-12 문자 형식)
    return \sprintf(
        '%s-%s-%s-%s-%s',
        \substr($md5, 0, 8),
        \substr($md5, 8, 4),
        \substr($md5, 12, 4),
        \substr($md5, 16, 4),
        \substr($md5, 20, 12)
    );
}

/**
 * 큰 숫자를 간결한 형식으로 포맷팅하는 함수.
 *
 * @param int    $number   포맷팅할 숫자
 * @param string $language 언어 코드 ('ko', 'zh', 'ja', 'en')
 *
 * @return string 포맷팅된 문자열
 */
function format_large_number($number, $language = 'ko')
{
    // 숫자가 0이면 바로 반환
    if (0 == $number) {
        return '0';
    }

    // 절대값으로 처리
    $absNumber = \abs((int) $number);

    // 언어별 단위 정의 (큰 단위부터 내림차순)
    $units = [
        'ko' => [
            1000000000000 => '조',
            100000000     => '억',
            10000         => '만',
            1000          => '천',
        ],
        'zh' => [
            1000000000000 => '兆',
            100000000     => '亿',
            10000         => '万',
            1000          => '千',
        ],
        'ja' => [
            1000000000000 => '兆',
            100000000     => '億',
            10000         => '万',
            1000          => '千',
        ],
        'en' => [
            1000000000000 => 'T',
            1000000000    => 'B',
            1000000       => 'M',
            1000          => 'K',
        ],
    ];

    // 언어가 지원되지 않을 경우 기본값은 영어
    if (!isset($units[$language])) {
        $language = 'en';
    }

    // 적합한 단위 찾기
    foreach ($units[$language] as $value => $unit) {
        if ($absNumber >= $value) {
            $formatted = $number / $value;

            // 정수인지 확인하여 소수점 표시 여부 결정
            if ($formatted == (int) $formatted) {
                return (int) $formatted . $unit;
            }

            // 소수점 한 자리까지 표시하고 필요없는 0 제거
            return \rtrim(\rtrim(number_format($formatted, 1), '0'), '.') . $unit;
        }
    }

    // 어떤 단위에도 해당하지 않으면 그대로 반환
    return number_format($number);
}

/**
 * 문장에 키워드가 포함되어 있는지 확인하는 함수.
 *
 * @param string $sentence      검색할 문장
 * @param string $keyword       찾을 키워드
 * @param bool   $caseSensitive 대소문자 구분 여부 (기본값: false)
 *
 * @return bool 키워드 포함 여부
 */
function keyword_contain($sentence, $keyword, $caseSensitive = false)
{
    if (empty($keyword)) {
        return false;
    }

    if ($caseSensitive) {
        return false !== \strpos($sentence, $keyword);
    }

    return false !== \stripos($sentence, $keyword);
}

/**
 * 문장에서 키워드를 찾아 HTML span 태그로 강조 표시하는 함수.
 *
 * @param string $sentence      원본 문장
 * @param string $keyword       강조할 키워드
 * @param string $className     span 태그에 적용할 CSS 클래스명
 * @param bool   $caseSensitive 대소문자 구분 여부 (기본값: false)
 *
 * @return string 키워드가 강조된 HTML 문자열
 */
function keyword_highlight($sentence, $keyword, $className = 'highlight', $caseSensitive = false)
{
    if (empty($keyword) || empty($sentence)) {
        return $sentence;
    }

    $flag = $caseSensitive ? '' : 'i';

    // 정규식 특수문자를 이스케이프 처리
    $escapedKeyword = \preg_quote($keyword, '/');

    // 키워드를 span 태그로 감싸기
    return \preg_replace(
        "/({$escapedKeyword})/{$flag}",
        "<span class=\"{$className}\">$1</span>",
        \htmlspecialchars($sentence)
    );
}

function getHttpHeader()
{
    $headers = [];

    foreach ($_SERVER as $key => $value) {
        if ('HTTP_' === \substr($key, 0, 5)) {
            $header_name = \str_replace(' ', '-', \ucwords(\str_replace('_', ' ', \strtolower(\substr($key, 5)))));

            if (!\in_array($header_name, ['Cookie', 'Host'])) {
                $headers[] = "{$header_name}: {$value}";
            }
        }
    }

    // Content-Type 헤더 추가 (HTTP_ 접두사가 없음)
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $headers[] = "Content-Type: {$_SERVER['CONTENT_TYPE']}";
    }

    return $headers;
}

/**
 * 추천 코드 생성 함수.
 *
 * 혼동 가능성이 적은 문자와 숫자를 사용하여 추천 코드를 생성합니다.
 * 0, 1, 2, 5, 8, B, I, L, O, S, Z 등 헷갈리기 쉬운 문자는 제외했습니다.
 *
 * @param int    $length             생성할 코드의 총 길이 (기본값: 8)
 * @param bool   $mixPattern         true: 문자/숫자 랜덤 섞기, false: 문자+숫자 순서대로 (기본값: false)
 * @param string $separator          구분자 문자 (빈 문자열이면 구분자 없음, 기본값: '')
 * @param bool   $avoidQuadDuplicate true: 4연속 중복 방지, false: 중복 허용 (기본값: false)
 *
 * @return string 생성된 추천 코드
 *
 * @example
 * // 기본 사용 - 8자리, 문자+숫자 분리
 * referral_code() // "ACEF3467"
 *
 * // 구분자 추가
 * referral_code(8, false, '-') // "ACEF-3467"
 *
 * // 랜덤 섞기 + 구분자
 * referral_code(8, true, '-') // "ACE4-G679"
 *
 * // 4연속 중복 방지
 * referral_code(8, false, '', true) // "AAAC3467" (AAAA 방지)
 *
 * @throws Exception random_int() 함수에서 발생할 수 있는 예외
 */
function referral_code(int $length = 8, bool $mixPattern = false, string $separator = '', bool $avoidQuadDuplicate = false) : string
{
    $allowedChars = 'ACDEFGHJKMNPQRTUVWXY';
    $allowedNums  = '34679';

    // 중복 체크 로직을 클로저로
    $getChar = function ($chars) use (&$code, $avoidQuadDuplicate) {
        $newChar = $chars[\random_int(0, \strlen($chars) - 1)];

        if ($avoidQuadDuplicate && \strlen($code) >= 3) {
            $lastThree = \substr($code, -3);

            if ($lastThree[0]    === $lastThree[1]
                && $lastThree[1] === $lastThree[2]
                && $lastThree[2] === $newChar) {
                $newChar = ($newChar === $chars[0]) ? $chars[1] : $chars[0];
            }
        }

        return $newChar;
    };

    $code = '';

    if ($mixPattern) {
        $allAllowed = $allowedChars . $allowedNums;

        for ($i = 0; $i < $length; ++$i) {
            $code .= $getChar($allAllowed);

            if ($separator && $i === (int) ($length / 2) - 1) {
                $code .= $separator;
            }
        }
    } else {
        $charsLength = (int) ceil($length / 2);

        // 문자 부분
        for ($i = 0; $i < $charsLength; ++$i) {
            $code .= $getChar($allowedChars);
        }

        if ($separator) {
            $code .= $separator;
        }

        // 숫자 부분
        for ($i = 0; $i < $length - $charsLength; ++$i) {
            $code .= $getChar($allowedNums);
        }
    }

    return $code;
}

/**
 * bluetools 앱의 버전 확인 함수.
 *
 * @param null|string $platform 확인할 플랫폼 (android, ios, 또는 null로 모든 플랫폼)
 * @param null|string $version  비교할 버전 (예: "1.0.0")
 *
 * @return bool 조건에 맞는지 여부
 */
function is_bluetools($platform = null, $version = null)
{
    // User-Agent 가져오기
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (empty($userAgent)) {
        return false;
    }

    // bluetools 문자열이 있는지 확인
    if (false === \strpos($userAgent, 'bluetools')) {
        return false;
    }

    // 특정 플랫폼 체크가 필요한 경우
    if (null !== $platform) {
        $platform = \strtolower($platform);

        // 해당 플랫폼 문자열이 있는지 확인
        if (false === \strpos($userAgent, $platform)) {
            return false;
        }
    }

    // 버전 체크가 필요 없는 경우 바로 true 반환
    if (null === $version) {
        return true;
    }

    // 버전 추출 시도
    $matches = [];

    if (\preg_match('/bluetools\s+(?:android|ios)?\s*(\d+(?:\.\d+){0,2})/', $userAgent, $matches)) {
        $currentVersion = $matches[1];

        // 버전 비교
        return \version_compare($currentVersion, $version, '>=');
    }

    // 버전 정보를 찾을 수 없는 경우
    return false; // 버전 정보가 없으면 false 반환 (요구사항에 따라 조정 가능)
}

function has_value($value)
{
    return null !== $value && '' !== $value && false !== $value;
}

function get_boolean($value)
{
    // null 값 처리
    if (\is_null($value)) {
        return false;
    }

    if (\is_string($value)) {
        // 문자열인 경우 소문자로 변환 후 비교
        $lowerValue = \strtolower($value);

        if ('true' === $lowerValue || '1' === $lowerValue || 'yes' === $lowerValue || 'y' === $lowerValue) {
            return true;
        }

        if ('false' === $lowerValue || '0' === $lowerValue || 'no' === $lowerValue || 'n' === $lowerValue || 'null' === $lowerValue) {
            return false;
        }
    }

    // 숫자, 불리언 또는 다른 값들
    return (bool) $value;
}

function check_tpl_path($decoded)
{
    return \preg_match('/^[A-Z].*\.tpl$/', $decoded);
}

function encrypt_token($data, $secretKey)
{
    $data['created_at'] = \time();

    return \Limepie\encrypt(\base64_encode(\serialize($data)), $secretKey);
}

function decrypt_token($token, $secretKey, $timeoutSeconds = 10)
{
    $decrypted = \Limepie\decrypt($token, $secretKey);
    $data      = \unserialize(\base64_decode($decrypted, true));

    if (!isset($data['created_at'])) {
        throw new \Exception('Token is invalid: missing created_at timestamp');
    }

    $timeElapsed = \time() - $data['created_at'];

    if ($timeElapsed > $timeoutSeconds) {
        throw new \Exception('Token has expired');
    }

    return $data;
}

function ajax_call($url, $formData, $authKey = null)
{
    $ch = \curl_init();

    $headers = [
        'X-Requested-With: XMLHttpRequest',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    ];

    if ($authKey) {
        $headers[] = 'Authorization: Bearer ' . $authKey;
    }

    \curl_setopt($ch, CURLOPT_URL, $url);
    \curl_setopt($ch, CURLOPT_POST, true);
    \curl_setopt($ch, CURLOPT_POSTFIELDS, \http_build_query($formData));
    // \curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36');
    \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    \curl_setopt($ch, CURLOPT_HEADER, true);

    $response = \curl_exec($ch);

    // 헤더와 바디 분리
    $headerSize = \curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header     = \substr($response, 0, $headerSize);
    $body       = \substr($response, $headerSize);
    $code       = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // 헤더 파싱
    $headers = [];

    foreach (\explode("\r\n", $header) as $line) {
        if (false !== \strpos($line, ':')) {
            [$key, $value]        = \explode(':', $line, 2);
            $headers[\trim($key)] = \trim($value);
        }
    }

    \curl_close($ch);

    return [
        $code,
        \trim($body),
        $headers,
    ];
}

function replace_timely($word)
{
    $words = [
        'day'   => 'daily',
        'week'  => 'weekly',
        'month' => 'monthly',
    ];

    return $words[$word] ?? $word;
}

// $str = "name=John | age=25 | city=Seoul";
// 결과:
// [
//     'name' => 'John',
//     'age' => '25',
//     'city' => 'Seoul'
// ]
function str_to_array($inputString, $mainDelimiter = '|', $keyValueDelimiter = '=')
{
    $result = [];
    $parts  = explode($mainDelimiter, $inputString);

    foreach ($parts as $part) {
        $keyValue     = explode($keyValueDelimiter, $part, 2);
        $key          = \trim($keyValue[0]);
        $value        = \trim($keyValue[1]);
        $result[$key] = $value;
    }

    return $result;
}

function str_replace_placeholder($text, $replace)
{
    return \str_replace('{0}', $replace, $text);
}
/*
// 테스트 케이스
$tests = [
    '<div>> <</div>',                     // 그대로 유지 (이미 공백 없음)
    '<div> > < </div>',                   // <div>> <</div> 로 변환
    '<테그> </테그>',                     // <테그></테그> 로 변환
    '<div >콘텐츠</div >',               // <div>콘텐츠</div> 로 변환
    '<div> > 콘텐츠 < </div>',           // <div>> 콘텐츠 <</div> 로 변환
    '<태그>   </태그>',                  // <태그></태그> 로 변환
];
 */

// 테그상의 > < 만 문제되므로 공백이 필요하면 &nbsp;를 사용하면 문제없을듯.
function remove_tag_whitespace($text)
{
    return \preg_replace([
        // '#\s+<#',         // 태그 시작 전 공백
        // '#>\s+#',         // 태그 끝 후 공백
        '#"\s+>#',        // 큰따옴표 끝과 태그 끝 사이 공백
        "#'\\s+>#",       // 작은따옴표 끝과 태그 끝 사이 공백
        '#\s+/>#',       // self-closing 태그 사이 공백
        // '#\s+><#',         // 태그 사이 공백
    ], [
        '">', "'>", '/>',
    ], $text);
}

function get_filename_from_url(string $url) : string
{
    $path = \parse_url($url, PHP_URL_PATH) ?? '';

    return \rawurlencode(\pathinfo($path, PATHINFO_BASENAME)) ?: '';
}

// $total보다 작은 max의 배수를 반환
function get_max_multiple($total, $max)
{
    if ($max <= 0) {
        throw new Exception('max는 0보다 커야 합니다.');
    }

    return \intdiv($total, $max) * $max;
}

function highlight_keyword($text, $keywords, $useHash = false, $className = 'highlight')
{
    // keywords가 배열인지 확인
    if (!\is_array($keywords)) {
        // \prx($keywords);

        return $text;

        throw new Exception('Keywords must be an array');
    }

    // 빈 배열 체크
    if (empty($keywords)) {
        return $text; // 키워드가 없으면 원본 텍스트 반환
    }

    // 각 키워드에 대해 preg_quote 적용
    $quotedKeywords = \array_map(function ($keyword) {
        return \preg_quote($keyword['name'], '/');
    }, $keywords);

    // 키워드들을 정규표현식 패턴으로 변환
    $keywordPattern = \implode('|', $quotedKeywords);

    if ($useHash) {
        $pattern = '/[#|＃](' . $keywordPattern . ')(?=[^\p{L}\p{N}]|$)/u';
    } else {
        $pattern = '/(' . $keywordPattern . ')(?=[^\p{L}\p{N}]|$)/u';
    }

    // 콜백 함수를 사용하여 매치된 키워드를 하이라이트된 버전으로 교체
    return \preg_replace_callback($pattern, function ($matches) use ($useHash, $className) {
        if ($useHash) {
            return '<span class="' . \htmlspecialchars($className) . '">#' . \htmlspecialchars($matches[1]) . '</span>';
        }

        return '<span class="' . \htmlspecialchars($className) . '">' . \htmlspecialchars($matches[0]) . '</span>';
    }, $text);
}

/**
 * 숫자를 레벨로 변환하는 함수.
 *
 * 2000포인트마다 1레벨씩 상승하는 시스템
 * 0~1999: 1레벨, 2000~3999: 2레벨, 4000~5999: 3레벨...
 *
 * @param int $number 변환할 숫자 (포인트, 경험치 등)
 *
 * @return int 계산된 레벨 (최소 1레벨부터 시작)
 *
 * @example
 * number_to_level(0)     // 1레벨 (시작 레벨)
 * number_to_level(1999)  // 1레벨 (아직 2레벨 못됨)
 * number_to_level(2000)  // 2레벨 (정확히 2레벨 달성)
 * number_to_level(5500)  // 3레벨 (2레벨을 넘어서 3레벨)
 */
function number_to_level(int $number) : int
{
    return (int) ($number / 2000) + 1;
}

/**
 * 레벨을 해당 레벨의 시작 숫자로 변환 (역함수).
 *
 * 특정 레벨이 되려면 최소 몇 포인트가 필요한지 계산
 *
 * @param int $level 레벨 (1 이상)
 *
 * @return int 해당 레벨의 시작 포인트
 *
 * @example
 * level_to_start_number(1)  // 0 (1레벨은 0포인트부터)
 * level_to_start_number(2)  // 2000 (2레벨은 2000포인트부터)
 * level_to_start_number(5)  // 8000 (5레벨은 8000포인트부터)
 */
function level_to_start_number(int $level) : int
{
    return ($level - 1) * 2000;
}

/**
 * 다음 레벨까지 필요한 포인트 계산.
 *
 * 현재 포인트에서 다음 레벨 달성까지 얼마나 더 필요한지 계산
 *
 * @param int $number 현재 포인트
 *
 * @return int 다음 레벨까지 부족한 포인트
 *
 * @example
 * points_to_next_level(1500)  // 500 (2000까지 500 부족)
 * points_to_next_level(3200)  // 800 (4000까지 800 부족)
 * points_to_next_level(2000)  // 2000 (이미 2레벨이므로 4000까지 2000 부족)
 */
function points_to_next_level(int $number) : int
{
    $current_level    = number_to_level($number);
    $next_level_start = level_to_start_number($current_level + 1);

    return $next_level_start - $number;
}

/**
 * 현재 레벨에서의 진행도 계산.
 *
 * 현재 레벨 내에서 얼마나 진행했는지 0~1999 범위로 반환
 *
 * @param int $number 현재 포인트
 *
 * @return int 현재 레벨 내 진행도 (0~1999)
 *
 * @example
 * get_level_progress(1500)  // 1500 (1레벨에서 1500만큼 진행)
 * get_level_progress(3200)  // 1200 (2레벨에서 1200만큼 진행)
 * get_level_progress(2000)  // 0 (2레벨 시작점이므로 진행도 0)
 */
function get_level_progress(int $number) : int
{
    return $number % 2000;
}

/**
 * 현재 레벨 진행률을 퍼센트로 계산.
 *
 * 현재 레벨에서 다음 레벨까지의 진행률을 0~100% 사이로 반환
 *
 * @param int $number 현재 포인트
 *
 * @return float 진행률 (0.0 ~ 100.0)
 *
 * @example
 * get_level_progress_percent(1000)  // 50.0 (1레벨에서 50% 진행)
 * get_level_progress_percent(3500)  // 75.0 (2레벨에서 75% 진행)
 */
function get_level_progress_percent(int $number) : float
{
    $progress = get_level_progress($number);

    return \round(($progress / 2000) * 100, 1);
}

/**
 * 레벨 정보를 배열로 반환 (종합 정보).
 *
 * 한 번의 함수 호출로 레벨 관련 모든 정보를 가져올 수 있음
 *
 * @param int $number 현재 포인트
 *
 * @return array 레벨 정보가 담긴 배열
 *
 * @example
 * get_level_info(3750)
 * // 반환값: [
 * //   'current_level' => 2,
 * //   'current_points' => 3750,
 * //   'level_progress' => 1750,
 * //   'points_to_next' => 250,
 * //   'progress_percent' => 87.5,
 * //   'next_level' => 3,
 * //   'next_level_start' => 4000
 * // ]
 */
function get_level_info(int $number) : array
{
    $current_level    = number_to_level($number);
    $level_progress   = get_level_progress($number);
    $points_to_next   = points_to_next_level($number);
    $progress_percent = get_level_progress_percent($number);

    return [
        'current_level'    => $current_level,
        'current_points'   => $number,
        'level_progress'   => $level_progress,
        'points_to_next'   => $points_to_next,
        'progress_percent' => $progress_percent,
        'next_level'       => $current_level + 1,
        'next_level_start' => level_to_start_number($current_level + 1),
    ];
}
function get_level($points)
{
    $level_config = [
        1  => 2000,     // 1-20
        21 => 3000,     // 21-40
        41 => 4000,     // 41-60
        61 => 5000,     // 61-80
        81 => 6000,     // 81+
    ];

    $current_points = $points;
    $level          = 1;      // 시작이 1레벨

    foreach ($level_config as $start_level => $point) {
        $points_needed = $point;

        while ($current_points >= $points_needed) {
            $current_points -= $points_needed;
            ++$level;
        }

        if ($current_points < $points_needed) {
            break;
        }
    }

    return $level;
}

/*

function get_level($points)
{
    $level_config = [
        1  => 2000,     // 1-20 레벨: 2000포인트씩
        21 => 3000,     // 21-40 레벨: 3000포인트씩
        41 => 4000,     // 41-60 레벨: 4000포인트씩
        61 => 5000,     // 61-80 레벨: 5000포인트씩
        81 => 6000,     // 81+ 레벨: 6000포인트씩
    ];

    $remaining_points = $points;
    $current_level    = 1;

    foreach ($level_config as $range_start => $points_per_level) {
        $range_end = \next($level_config) ? \key($level_config) - 1 : PHP_INT_MAX;

        // 현재 구간에서 레벨업이 가능한 동안 반복
        while ($current_level >= $range_start
               && $current_level <= $range_end
               && $remaining_points >= $points_per_level) {
            $remaining_points -= $points_per_level;
            ++$current_level;
        }
    }

    return $current_level;
}

*/
function wrap_space_em($text)
{
    // 첫 번째 공백을 찾습니다.
    $pos = \strpos($text, ' ');

    // 공백이 없으면 원래 텍스트를 그대로 반환합니다.
    if (false === $pos) {
        return $text;
    }

    // 첫 번째 부분(공백 전까지)과 두 번째 부분(공백 포함 이후)을 나눕니다.
    $firstPart  = \substr($text, 0, $pos);
    $secondPart = \substr($text, $pos);

    // 두 번째 부분을 <em> 태그로 감쌉니다.
    return \trim($firstPart) . '<em>' . \trim($secondPart) . '</em>';
}
function star_item_percentage($averagePercentage, $starNumber)
{
    // 입력값 유효성 검사
    if ($averagePercentage < 0 || $averagePercentage > 100) {
        throw new Exception('Average percentage must be between 0 and 100');
    }

    if ($starNumber < 1 || $starNumber > 5) {
        throw new Exception('Star number must be between 1 and 5');
    }

    // 별 하나당 백분율 계산 (20%씩)
    $percentagePerStar = 20;

    // 해당 별의 최대 백분율 계산
    $maxPercentageForStar = $starNumber * $percentagePerStar;

    // 해당 별의 채워짐 정도 계산
    return \max(0, \min(100, ($averagePercentage - ($starNumber - 1) * $percentagePerStar) / $percentagePerStar * 100));
}

function get_hash_tags($text)
{
    $pattern = '/[＃|#]([\p{L}\p{N}_]+)/u';
    \preg_match_all($pattern, $text, $matches);

    if (isset($matches[1]) && $matches[1] && \is_array($matches[1])) {
        return \array_unique($matches[1]);
    }

    return [];
}

function clean_tags($input)
{
    $input   = \strip_tags($input, '<div><img><p><br>');
    $pattern = '/<(p|div)([^>]*)>|<img([^>]*\ssrc=["\']([^"\']*)["\'][^>]*)>/i';

    $replacement = function ($matches) {
        if (!empty($matches[1])) {
            // <p>, <div>, <span> 태그
            return "<{$matches[1]}>";
        }

        if (!empty($matches[4])) {
            // <img> 태그
            return "<img src=\"{$matches[4]}\">";
        }

        return $matches[0];
    };

    return \preg_replace_callback($pattern, $replacement, $input);
}

function get_age_group($birthdate, $language = 'en')
{
    // 현재 날짜와 시간을 가져옵니다.
    $currentDate = new DateTime();

    // 생일 문자열을 DateTime 객체로 변환합니다.
    $birthDate = new DateTime($birthdate);

    // 나이를 계산합니다.
    $age = $currentDate->diff($birthDate)->y;

    // 나이대를 계산합니다.
    $ageGroup = \floor($age / 10) * 10;

    // 언어별 형식 정의
    $formats = [
        'en' => function ($ageGroup) {
            return ($ageGroup >= 100) ? '100 and above' : $ageGroup . 's';
        },
        'ko' => function ($ageGroup) {
            return ($ageGroup >= 100) ? '100세 이상' : $ageGroup . '대';
        },
        'ja' => function ($ageGroup) {
            return ($ageGroup >= 100) ? '100歳以上' : $ageGroup . '代';
        },
        'zh' => function ($ageGroup) {
            return ($ageGroup >= 100) ? '100岁及以上' : $ageGroup . '多岁';
        },
        // 다른 언어를 여기에 추가할 수 있습니다.
    ];

    // 지원하지 않는 언어의 경우 영어를 기본값으로 사용합니다.
    if (!isset($formats[$language])) {
        $language = 'en';
    }

    // 해당 언어의 형식에 따라 나이대를 반환합니다.
    return $formats[$language]($ageGroup);
}
function thumb_url($url, $param)
{
    // URL 파싱
    $parsedUrl = \parse_url($url);

    // 경로와 쿼리 문자열 분리
    $path = $parsedUrl['path'];

    // 새로운 경로 생성
    $newPath = '/' . $param . $path;

    // 새로운 URL 조합
    return (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '')
              . (isset($parsedUrl['host']) ? $parsedUrl['host'] : '')
              . $newPath
              . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '')
              . (isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '');
}

// 사용 예시
// Base: /a/b/c, Relative: .
//   keepCurrent false: /a/b
//   keepCurrent true:  /a/b/c

// Base: /a/b/c, Relative: ..
//   keepCurrent false: /a
//   keepCurrent true:  /a/b

function adjust_path($basePath, $relativePath, $keepCurrent = true)
{
    $basePath = '/' . \trim($basePath, '/');
    $parts    = \array_filter(explode('/', $basePath), 'strlen');

    if ('' === $relativePath || ('.' === $relativePath && $keepCurrent)) {
        return '/' . \implode('/', $parts);
    }

    $relativeParts = explode('/', $relativePath);
    $skipCount     = 0;

    foreach (\array_reverse($relativeParts) as $part) {
        if ('.' === $part && !$keepCurrent) {
            continue;
        }

        if ('..' === $part) {
            ++$skipCount;
        } else {
            if ($skipCount > 0) {
                --$skipCount;
            } else {
                \array_unshift($parts, $part);
            }
        }
    }

    if ($keepCurrent) {
        $parts = \array_slice($parts, 0, count($parts) - $skipCount);
    } else {
        $parts = \array_slice($parts, $skipCount);
    }

    return '/' . \implode('/', $parts);
}

function utc_date($format)
{
    $utcDate = new \DateTime('now', new \DateTimeZone('UTC'));

    return $utcDate->format($format);
}
// 받침 유무 판별 함수
function has_final_consonant($char)
{
    $code = \mb_ord($char) - 44032;

    return 0 != $code % 28;
}
function add_josa($word, $josa)
{
    // UTF-8 인코딩으로 문자열을 처리합니다.
    $code = \mb_substr($word, -1, 1, 'UTF-8');
    $len  = \strlen($code);

    // 한글인지, 숫자인지, 영문인지 판별
    if (\preg_match('/[0-9]/', $code)) {
        $last_char_type = 'number';
    } elseif (\preg_match('/[a-zA-Z]/', $code)) {
        $last_char_type = 'english';
    } elseif ($len > 1) {
        $last_char_type = 'korean';
    } else {
        $last_char_type = 'other';
    }

    // 조사를 선택합니다.
    switch ($josa) {
        case '은':
        case '는':
            if ('korean' == $last_char_type && has_final_consonant($code)) {
                return $word . '은';
            }

            return $word . '는';
        case '이':
        case '가':
            if ('korean' == $last_char_type && has_final_consonant($code)) {
                return $word . '이';
            }

            return $word . '가';
        case '와':
        case '과':
            if ('korean' == $last_char_type && has_final_consonant($code)) {
                return $word . '과';
            }

            return $word . '와';
        case '을':
        case '를':
            if ('korean' == $last_char_type && has_final_consonant($code)) {
                return $word . '을';
            }

            return $word . '를';

        default:
            return $word;
    }
}

function decamelize($word)
{
    if (false === \strpos($word, ' ')) {
        return \str_replace(['(', ')'], ['_(', '_)'], \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $word)));
    }

    return $word;
}

// function camelize(string $word)
// {
//     return \preg_replace_callback(
//         '/(^|_|-|:|\/)([a-zA-Z]+)/',
//         function ($m) {
//             return \ucfirst(\strtolower("{$m[2]}"));
//         },
//         $word
//     );
// }
function camelize(string $word, string $delimiters = '')
{
    return \preg_replace_callback(
        '/(^|\||_|-|:|\/|\\\)([a-zA-Z]+)/',
        function ($m) use ($delimiters) {
            return ($delimiters && \in_array($m[1], \str_split($delimiters)) ? $m[1] : '') . \ucfirst(\strtolower("{$m[2]}"));
        },
        $word
    );
}

function safe_input_value($string = '')
{
    // input tag에는 따옴표가 문제가 될수 있다.
    return \str_replace(['"', "'"], '', $string);
}

function per1($point, $step)
{
    $diff = $point - $step + 1;

    if (1 <= $diff) {
        return 100;
    }

    if (0 < $diff && 1 > $diff) {
        return $diff * 100;
    }

    return 0;
}
function repairSerializeString($value)
{
    $regex = '/s:([0-9]+):"(.*?)"/';

    return \preg_replace_callback(
        $regex,
        function ($match) {
            return 's:' . \mb_strlen($match[2]) . ':"' . $match[2] . '"';
        },
        $value
    );
}
function unserialize($value)
{
    $org   = $value;
    $value = \preg_replace_callback(
        '/(?<=^|\{|;)s:(\d+):\"(.*?)\";(?=[asbdiO]\:\d|N;|\}|$)/s',
        function ($m) {
            return 's:' . \strlen($m[2]) . ':"' . $m[2] . '";';
        },
        $value
    );

    // if ($org !== $value) {
    //     \pr($org, $value);
    // }
    // $value = \utf8_encode($value);
    // $value = \Limepie\repairSerializeString($value);
    // echo '1';

    // \var_dump($org);
    // \var_dump($value);

    try {
        return \unserialize($value);
    } catch (\Exception $e) {
        // \pr($org);
        // \pr($value);

        try {
            return \unserialize($org);
        } catch (\Exception $e) {
            // \pr($org);

            throw $e;
        }
    }
}

/**
 * file을 읽어 확장자에 따라 decode하여 리턴.
 *
 * @return string
 */
function decode_file(string $filename) : array
{
    if (false === \file_exists($filename)) {
        throw new Exception($filename . ' file not exists');
    }
    $contents = \file_get_contents($filename);
    $ext      = \pathinfo($filename, \PATHINFO_EXTENSION);

    switch ($ext) {
        case 'yaml':
        case 'yml':
            $result = \yaml_parse($contents);

            break;
        case 'json':
            $result = \json_decode($contents, true);

            if ($type = \json_last_error()) {
                switch ($type) {
                    case \JSON_ERROR_DEPTH:
                        $message = 'Maximum stack depth exceeded';

                        break;
                    case \JSON_ERROR_CTRL_CHAR:
                        $message = 'Unexpected control character found';

                        break;
                    case \JSON_ERROR_SYNTAX:
                        $message = 'Syntax error, malformed JSON';

                        break;
                    case \JSON_ERROR_NONE:
                        $message = 'No errors';

                        break;
                    case \JSON_ERROR_UTF8:
                        $message = 'Malformed UTF-8 characters';

                        break;

                    default:
                        $message = 'Invalid JSON syntax';
                }

                throw new Exception($filename . ' ' . $message);
            }

            break;

        default:
            throw new Exception($ext . ' not support');

            break;
    }

    return $result;
}

function ceil(float $val, int $precision = 0)
{
    $x = 1;

    for ($i = 0; $i < $precision; ++$i) {
        $x *= 10;
    }

    return \ceil($val * $x) / $x;
}

function legacy_yml_parse_file($file, ?\Closure $callback = null)
{
    $filepath = \Limepie\stream_resolve_include_path($file);

    if ($filepath) {
        $basepath = \dirname($filepath);
        $spec     = \yaml_parse_file($filepath);

        $data = arr::legacy_refparse($spec, $basepath);

        if (true === isset($callback) && $callback) {
            return $callback($data);
        }

        return $data;
    }

    throw new Exception('"' . $file . '" file not found');
}

function yml_parse_file($file, ?\Closure $callback = null)
{
    $filepath = \Limepie\stream_resolve_include_path($file);

    if ($filepath) {
        $basepath = \dirname($filepath);
        $spec     = \yaml_parse_file($filepath);

        $formProcessor = new Form\Parser($spec, $basepath);

        $data = $formProcessor->processForm();

        if (true === isset($callback) && $callback) {
            return $callback($data);
        }

        return $data;
    }

    throw new Exception('"' . $file . '" file not found');
}

function yml_parse($body, ?\Closure $callback = null)
{
    $spec = \yaml_parse($body);

    $formProcessor = new Form\Parser($spec);

    $data = $formProcessor->processForm();

    if (true === isset($callback) && $callback) {
        return $callback($data);
    }

    return $data;
}

/**
 * 숫자를 읽기쉬운 문자열로 변환.
 *
 * @param mixed $bytes
 * @param mixed $decimals
 */
function readable_size($bytes, $decimals = 2) : string
{
    $size   = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = \floor((\strlen((string) $bytes) - 1) / 3);

    return \sprintf("%.{$decimals}f", $bytes / \pow(1024, $factor)) . @$size[$factor];
}

function get_language() : string
{
    return Cookie::get(Cookie::getKeyStore('language'));
}

function get_locale() : string
{
    return Cookie::get(Cookie::getKeyStore('locale'));
}

function file_put_contents($filename, mixed $data, int $flags = 0, $context = null)
{
    $foldername = \dirname($filename);
    \Limepie\create_dir($foldername);

    return \file_put_contents($filename, $data, $flags, $context);
}

function create_dir($dir, $permissions = 0755)
{
    if (!\file_exists($dir)) {
        $dirs       = explode('/', $dir);
        $createPath = '';

        foreach ($dirs as $part) {
            $createPath .= $part . '/';

            if (!\is_dir($createPath)) {
                if (!\mkdir($createPath)) {
                    throw new Exception('Cannot create directory: ' . $createPath);
                }
                \chmod($createPath, $permissions);
            }
        }
    }

    return true;
}

function mkdir($dir)
{
    if (false === \is_file($dir)) {
        $dirs       = \explode('/', $dir);
        $createPath = '';

        for ($dirIndex = 0, $dirCount = \count($dirs); $dirIndex < $dirCount; ++$dirIndex) {
            $createPath .= $dirs[$dirIndex] . '/';

            if (false === \is_dir($createPath)) {
                if (false === \mkdir($createPath)) {
                    throw new Exception('cannot create asserts directory <b>' . $createPath . '</b>');
                }
                \chmod($createPath, 0777);
            }
        }
    }
}

// function is_boolean_typex($var)
// {
//     $result = \Limepie\is_boolean_type2($var);

//     \ob_start(); // 출력 버퍼링을 켭니다
//     echo \var_dump($var);
//     $tmp = \ob_get_contents(); // 출력 버퍼의 내용을 반환
//     \ob_end_clean();

//     \pr($var, $result, $tmp);

//     return $result;
// }

function is_boolean_type($var)
{
    if (true === \is_int($var)) {
        return true;
    }

    if (true === \is_numeric($var)) {
        return true;
    }

    if (true === \is_bool($var)) {
        return true;
    }

    if (true === \is_array($var)) {
        return false;
    }

    switch (\strtolower($var)) {
        case '1':
        case 'true':
        case 'on':
        case 'yes':
        case 'y':
        case '0':
        case 'false':
        case 'off':
        case 'no':
        case 'n':
        case '':
            return true;

        default:
            return false;
    }
}

function is_enabled($variable)
{
    if (false === isset($variable)) {
        return null;
    }

    return \filter_var($variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
}

function createTree($array)
{
    switch (count($array)) {
        case 0:
            exit('Illegal argument.');
        case 1:
            return $array[0];

        default:
            $lastArray = \array_pop($array);
            $subArray  = \Limepie\createTree($array);

            foreach ($lastArray as $item) {
                $return[] = [$item, $subArray];
            }

            return $return;
    }
}

function generateStandardCode($length = 12, $prefix = 'P')
{
    // microtime(true) 값을 문자열로 변환하고, 소수점을 제거
    $microtimeStr = \str_replace('.', '', (string) \microtime(true));

    // 마지막 6자리를 추출
    $baseCode = $prefix . \substr($microtimeStr, -6);

    // 필요한 길이만큼 랜덤 숫자 생성
    $randomLength = $length - \strlen($baseCode);
    $randomDigits = '';

    for ($i = 0; $i < $randomLength; ++$i) {
        $randomDigits .= \mt_rand(0, 9);
    }

    // 최종 코드 반환
    return $baseCode . $randomDigits;
}

/**
 * Generate a unique ID.
 */
function uniqid(int $length = 13) : string
{
    if (true === \function_exists('random_bytes')) {
        $bytes = \random_bytes((int) \ceil($length / 2));
    } elseif (true === \function_exists('openssl_random_pseudo_bytes')) {
        $bytes = \openssl_random_pseudo_bytes((int) \ceil($length / 2));
    } else {
        $bytes = \md5((string) \mt_rand());
    }

    return \substr(\bin2hex($bytes), 0, $length);
}

function random_string($length = 5)
{
    return \Limepie\genRandomString($length);
}

function genRandomString($length = 5)
{
    // $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $char .= 'abcdefghijklmnopqrstuvwxyz';
    // $char .= '0123456789';
    $char = 'abcdefghjkmnpqrstuvwxyz';
    $char .= '23456789';
    $result = '';

    for ($i = 0; $i < $length; ++$i) {
        $result .= $char[\mt_rand(0, \strlen($char) - 1)];
    }

    return $result;
}

function guid($l = 10)
{
    $str = '';

    for ($x = 0; $x < $l; ++$x) {
        $str .= \substr(\str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 1);
    }

    return $str;
}
function define($key, $name)
{
    if (\defined($key)) {
    } else {
        \define($key, $name);
    }
}
function is_ajax()
{
    return true === isset($_SERVER['HTTP_X_REQUESTED_WITH'])
           && ('xmlhttprequest' === \strtolower(\getenv('HTTP_X_REQUESTED_WITH')));
}

function is_post()
{
    return 'POST' === $_SERVER['REQUEST_METHOD'];
}

function is_cli() : bool
{
    if (true === isset($_ENV['is_swoole']) && 1 === (int) $_ENV['is_swoole']) {
        return false;
    }

    return 'cli' === \php_sapi_name();
}

/**
 * 주어진 쿼리 스트링이 현재 URL의 쿼리 스트링에 포함되어 있는지 확인하는 함수.
 *
 * @param string $queryString 확인할 쿼리 스트링 (예: "type=1&sort=2")
 *
 * @return bool 포함 여부 (true/false)
 */
function match_qs($queryString)
{
    if (!$queryString) {
        return true;
    }

    // 현재 URL의 쿼리 스트링 가져오기
    $currentQueryString = $_SERVER['QUERY_STRING'] ?? '';

    // 비교할 쿼리 스트링을 배열로 파싱
    \parse_str($queryString, $queryParams);

    // 현재 URL의 쿼리 스트링을 배열로 파싱
    \parse_str($currentQueryString, $currentParams);

    // 모든 요구 파라미터가 현재 URL에 있고 값이 일치하는지 확인
    foreach ($queryParams as $key => $value) {
        // 키가 존재하지 않거나 값이 다른 경우
        if (!isset($currentParams[$key]) || $currentParams[$key] != $value) {
            return false;
        }
    }

    // 모든 파라미터가 일치하면 true 반환
    return true;
}

function random_uuid()
{
    return \uuid_create(\UUID_TYPE_RANDOM);
}

/**
 * UUID 생성 함수
 * RFC9562 UUIDs(v6, v7)를 지원하는 util-linux 2.41 이상에서는 UUID v7을 사용.
 *
 * @param int $type UUID 타입 (기본값: UUID_TYPE_TIME)
 *
 * @return string 생성된 UUID 문자열
 */
function uuid(int $type = \UUID_TYPE_TIME) : string
{
    // UUID v7 지원 확인 및 사용 (util-linux 2.41 이상)
    if (\UUID_TYPE_TIME === $type) {
        if (\defined('UUID_TYPE_TIME_V7')) {
            return \uuid_create(\UUID_TYPE_TIME_V7);
        }

        return \Limepie\uuid7();
    }

    // 참고: UUID_TYPE_DCE는 더 이상 사용되지 않음 (대신 UUID_TYPE_RANDOM 사용)
    // 참고: UUID_TYPE_NAME은 더 이상 사용되지 않음 (대신 UUID_TYPE_TIME 사용)

    // 기존 타입 사용
    return \uuid_create($type);
}

/**
 * RFC 9562 명세를 따르는 UUID v7 생성.
 *
 * UUID v7 형식:
 * - 처음 48비트: Unix 타임스탬프(밀리초)
 * - 다음 12비트: 시퀀스 카운터 또는 랜덤
 * - 2비트: 변형(10xx)
 * - 4비트: 버전(0111)
 * - 나머지 62비트: 랜덤 데이터
 *
 * @return string 표준 UUID v7 문자열
 */
function uuid7()
{
    static $last_timestamp = 0;
    static $counter        = 0;

    // 현재 시간 (밀리초 단위)
    $timestamp_ms = (int) (\microtime(true) * 1000);

    // 타임스탬프 충돌 처리
    if ($timestamp_ms === $last_timestamp) {
        $counter = ($counter + 1) & 0xFFF;

        if (0 === $counter) {
            ++$timestamp_ms;
        }
    } else {
        $counter        = 0;
        $last_timestamp = $timestamp_ms;
    }

    // 48비트 타임스탬프를 16진수로 변환
    $time_hex = \str_pad(\dechex($timestamp_ms), 12, '0', STR_PAD_LEFT);

    // 카운터를 16진수로 변환
    $counter_hex = \str_pad(\dechex($counter), 3, '0', STR_PAD_LEFT);

    // 랜덤 데이터 생성 (충분한 양의 랜덤 바이트 확보)
    $random_bytes = \random_bytes(16); // 충분한 랜덤 바이트 생성

    // 버전 비트 설정 (버전 7)
    $version_byte    = \chr((\ord($random_bytes[0]) & 0x0F) | 0x70);
    $random_bytes[0] = $version_byte;

    // 변형 비트 설정 (RFC 4122 변형)
    $variant_byte    = \chr((\ord($random_bytes[1]) & 0x3F) | 0x80);
    $random_bytes[1] = $variant_byte;

    // 랜덤 바이트를 16진수로 변환
    $random_hex = \bin2hex($random_bytes);

    // UUID 형식으로 조합
    return \sprintf(
        '%s-%s-%s-%s-%s',
        \substr($time_hex, 0, 8),          // 8자
        \substr($time_hex, 8, 4),          // 4자
        '7' . \substr($counter_hex, 0, 3), // 4자 (버전 + 카운터)
        \substr($random_hex, 2, 4),        // 4자 (변형 비트 포함)
        \substr($random_hex, 6, 12)        // 12자
    );
}

// 사용 예시
// $uuid = uuid7();
// echo "Generated UUID v7: " . $uuid;

// The code is inspired by the following discussions and post:
// http://stackoverflow.com/questions/5483851/manually-parse-raw-http-data-with-php/5488449#5488449
// http://www.chlab.ch/blog/archives/webdevelopment/manually-parse-raw-http-data-php

/**
 * Parse raw HTTP request data.
 *
 * Pass in $appends as an array. This is done by reference to avoid copying
 * the data around too much.
 *
 * Any files found in the request will be added by their field name to the
 * $data['files'] array.
 *
 * @param   array  Empty array to fill with data
 * @param mixed $appends
 *
 * @return array Associative array of request data
 */
function parse_raw_http_request($appends = [])
{
    // read incoming data
    $input = \file_get_contents('php://input');

    // grab multipart boundary from content type header
    \preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);

    // content type is probably regular form-encoded
    if (!count($matches)) {
        // we expect regular puts to containt a query string containing data
        \parse_str(\urldecode($input), $appends);

        return $appends;
    }

    $boundary = $matches[1];

    // split content by boundary and get rid of last -- element
    $boundaryBlocks = \preg_split("/-+{$boundary}/", $input);
    \array_pop($boundaryBlocks);

    $keyValueStr = '';

    // loop data blocks
    foreach ($boundaryBlocks as $id => $block) {
        if (empty($block)) {
            continue;
        }

        // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

        // parse uploaded files
        if (false !== \strpos($block, 'application/octet-stream')) {
            // match "name", then everything after "stream" (optional) except for prepending newlines
            \preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            $appends['files'][$matches[1]] = $matches[2];
        }
        // parse all other fields
        else {
            // match "name" and optional value in between newline sequences
            \preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            $keyValueStr .= $matches[1] . '=' . ($matches[2] ?? '') . '&';
        }
    }
    $keyValueArr = [];
    \parse_str($keyValueStr, $keyValueArr);

    return \array_merge($appends, $keyValueArr);
}

function stream_resolve_include_path($filename)
{
    $includePaths = \explode(\PATH_SEPARATOR, \get_include_path());

    \array_unshift($includePaths, '');

    foreach ($includePaths as $path) {
        if ('.' === $path) {
            continue;
        }
        $includeFilename = ($path ? $path . '/' : '') . $filename;

        if (true === \file_exists($includeFilename)) {
            return \realpath($includeFilename);
        }
    }

    return false;
}

// https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5
function cidr_match($ip, $cidr)
{
    $outcome = false;
    $pattern = '/^(([01]?\d?\d|2[0-4]\d|25[0-5])\.){3}([01]?\d?\d|2[0-4]\d|25[0-5])\/(\d{1}|[0-2]{1}\d{1}|3[0-2])$/';

    if (\preg_match($pattern, $cidr)) {
        [$subnet, $mask] = \explode('/', $cidr);

        if (\ip2long($ip) >> (32 - $mask) === \ip2long($subnet) >> (32 - $mask)) {
            $outcome = true;
        }
    }

    return $outcome;
}

function url()
{
    $request = Di::getRequest();

    return $request->getUrl();
}

function is_binary(string $string) : bool
{
    if (!\ctype_print($string)) {
        return true;
    }

    return false;
}

function decimal($number, $zero2null = false) : null|float|int
{
    if (true === $zero2null) {
        if (null === $number || 0 === (float) $number) {
            return null;
        }
    }

    if (0 < \strlen((string) $number)) {
        $parts  = \explode('.', (string) $number);
        $result = $parts[0];

        if (true === isset($parts[1])) {
            if ($r = \rtrim($parts[1], '0')) {
                $result .= '.' . $r;

                return (float) $result;
            }
        }

        return (int) $result;
    }

    return (int) 0;
}

function ffc($number)
{
    return \Limepie\float_format($number, true, false, true);
}

function float_format($number, $int = true, $zero2null = false, $comma = true)
{
    if ($zero2null && (\is_null($number) || 0.0 === (float) $number)) {
        return null;
    }

    $parts       = explode('.', (string) $number);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '';

    if (true === $int) {
        $decimalPart = \rtrim($decimalPart, '0');
    } elseif (\is_numeric($int)) {
        $decimalPart = \rtrim(\substr($decimalPart, 0, $int), '0');
    } else {
        $decimalPart = '';
    }

    if ($comma) {
        $integerPart = number_format($integerPart, 0, '.', ',');
    }

    return $decimalPart ? "{$integerPart}.{$decimalPart}" : $integerPart;
}

// number comma
function nc($number)
{
    $parts       = \explode('.', (string) $number); // 정수 부분과 소수 부분으로 분리합니다.
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '';

    $formattedInteger = \number_format((int) $integerPart); // 정수 부분을 천 단위로 구분하여 형식화합니다.

    // 소수 부분이 있으면서 소수 부분이 0으로만 구성되어 있지 않을 경우, 소수 부분을 trim 합니다.
    $formattedDecimal = !empty($decimalPart) && !\preg_match('/^0+$/', $decimalPart) ? \rtrim($decimalPart, '0') : '';

    // 형식화된 정수 부분과 소수 부분을 조합하여 최종 결과를 반환합니다.
    return $formattedDecimal ? $formattedInteger . '.' . $formattedDecimal : $formattedInteger;
}
function nf($number, $int = 0)
{
    return \Limepie\number_format($number, $int);
}
function number_format($number, $int = 0, $zero2null = false)
{
    if (true === $zero2null) {
        if (null === $number || 0 === (float) $number) {
            return null;
        }
    }
    // $stripzero = sprintf('%g',$number);

    if (0 < \strlen((string) $number)) {
        $parts  = \explode('.', (string) $number);
        $result = \number_format((int) $parts[0]);

        if (true === isset($parts[1])) {
            if ($r = \rtrim(\substr($parts[1], 0, $int), '0')) {
                $result .= '.' . $r;
            }
        }

        return $result;
    }

    return 0;
}

function count($target)
{
    if ($target instanceof \Traversable) {
        return \iterator_count($target);
    }

    return \count($target);
}

function seqtoid($seq)
{
    return '-' . \str_pad((string) $seq, 11, '0', \STR_PAD_LEFT) . '-';
}

function seqtokey($seq)
{
    if (true === \is_numeric($seq)) {
        return '__' . \str_pad((string) $seq, 13, '0', \STR_PAD_LEFT) . '__';
    }

    return $seq;
}

function seq2key($seq)
{
    return \Limepie\seqtokey($seq);
}

function keytoseq($key)
{
    if (1 === \preg_match('#^__([0]+)?(?P<seq>\d+)__$#', (string) $key, $m)) {
        return (int) $m['seq'];
    }

    return null;
}

function idtoseq($key)
{
    if (1 === \preg_match('#^-([0]+)?(?P<seq>\d+)-$#', $key, $m)) {
        return (int) $m['seq'];
    }

    return null;
}

function key2seq($key)
{
    return \Limepie\keytoseq($key);
}

function strtoint($str)
{
    return \gmp_intval(\gmp_init($str));
}

function http_build_query($data = [], $glue = '=', $separator = '&', $encode = false)
{
    $results = [];
    $isAssoc = arr::is_assoc($data);

    foreach ($data as $k => $v) {
        if (true === \is_array($v)) {
            $results[] = ($k ? $k . $glue : '') . '[' . http_build_query($v, $glue, $separator, $encode) . ']';
        } else {
            if (true === $encode) {
                $v = \htmlspecialchars($v);
            }

            if ($isAssoc) {
                $results[] = ($k ? $k . $glue : '') . $v;
            } else {
                $results[] = $v;
            }
        }
    }

    return \implode($separator, $results);
}

function getIp()
{
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($keys as $key) {
        if (! empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);

            foreach ($ips as $ip) {
                $ip = \trim($ip);

                // 공인·사설·예약 구분 없이 유효한 IP만 허용
                if (\filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }

    return '127.0.0.1';
}

function inet6_ntop($ip)
{
    $l = \strlen($ip);

    if (4 === $l or 16 === $l) {
        return \inet_ntop(\pack('A' . $l, $ip));
    }

    return '';
}

function inet_aton($ip)
{
    $ip = \trim($ip);

    if (\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
        return 0;
    }

    return \sprintf('%u', \ip2long($ip));
}

function inet_ntoa($num)
{
    $num = \trim($num);

    if ('0' === $num) {
        return '0.0.0.0';
    }

    return \long2ip(-(4294967295 - ($num - 1)));
}

/**
 * Produce a version of the AES key in the same manor as MySQL.
 *
 * @param string $key
 *
 * @return string
 *
 * @see https://www.smashingmagazine.com/2012/05/replicating-mysql-aes-encryption-methods-with-php/
 */
function mysql_aes_key($key)
{
    $bytes  = 16;
    $newKey = \str_repeat(\chr(0), $bytes);
    $length = \strlen($key);

    for ($i = 0; $i < $length; ++$i) {
        $index = $i % $bytes;
        $newKey[$index] ^= $key[$i];
    }

    return $newKey;
}

/**
 * \putenv('AES_SALT=1234567890abcdefg');
 * Programmatically mimic a MySQL AES_ENCRYPT() action as a way of avoiding unnecessary database calls.
 *
 * @param string     $decrypted
 * @param null|mixed $salt
 *
 * @return string
 */
function aes_encrypt($decrypted, $salt = null)
{
    if (null === $salt) {
        if (!($salt = \getenv('AES_SALT'))) {
            throw new Exception('Missing encryption salt.');
        }
    }

    $key = \Limepie\mysql_aes_key($salt);

    $cypher = 'aes-128-ecb';

    return \openssl_encrypt($decrypted, $cypher, $key, \OPENSSL_RAW_DATA);
}

/**
 * Programmatically mimic a MySQL AES_DECRYPT() action as a way of avoiding unnecessary database calls.
 *
 * @param string     $encrypted
 * @param null|mixed $salt
 *
 * @return string
 */
function aes_decrypt($encrypted, $salt = null)
{
    if (null === $salt) {
        if (!($salt = \getenv('AES_SALT'))) {
            throw new Exception('Missing encryption salt.');
        }
    }

    $key = \Limepie\mysql_aes_key($salt);

    $cypher = 'aes-128-ecb';

    return \openssl_decrypt($encrypted, $cypher, $key, \OPENSSL_RAW_DATA);
}

function ato($array)
{
    return arr::to_object($array);
}

function add_slash($string)
{
    $wichs = \func_get_args();
    \array_shift($wichs);

    $test = [];

    $length = 0;

    foreach ($wichs as $wich) {
        $test[] = \substr($string, $length, $wich);
        $length += $wich;
    }
    $b = \substr($string, $length);

    return \implode('/', $test) . '/' . $b;
}

function get_tree(array $data = []) : array
{
    $permalink = new Menu('', '');

    foreach ($data as $row) {
        $permalink->addSeq($row['name'], $row['current_seq'], '', $row['parent_seq'], ['seq' => $row['seq']]);
    }

    return $permalink->get(0);
}

function check_zipcode($zipcode, $locale = 'ko')
{
    if ('jp' === $locale) {
        return \preg_match('/^\d{3}[-]?\d{4}$/', $zipcode);
    }

    return \preg_match('/^\d{5}$/', $zipcode) || 5 == \strlen($zipcode);
}

function get_tree_item(array $data = []) : array
{
    $permalink = new Menu('', '');

    foreach ($data as $row) {
        $permalink->addSeq($row['name'], $row['current_seq'], '', $row['parent_seq'], ['seq' => $row['seq']]);
    }

    $iterator = new \RecursiveIteratorIterator(
        new AdjacencyList($permalink->menu),
        \RecursiveIteratorIterator::SELF_FIRST
    );

    $items = [];

    foreach ($iterator as $key => $value) {
        // Build long key name based on parent keys
        $names = [];

        for ($i = 0; $iterator->getDepth() >= $i; ++$i) {
            $it      = $iterator->getSubIterator($i)->current();
            $names[] = $it['name'];
        }

        $items[$value['params']['seq']] = \implode(' > ', $names);
    }

    return $items;
}

function get_parent_controller_namespace($namespace = null, $depth = 1)
{
    if (null === $namespace) {
        return null;
    }

    return '\\' . \implode('\\', \array_slice(\explode('\\', $namespace), 0, $depth * -2 + 1)) . '\Controller';
}

function file_get_compressed($url)
{
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'Accept-Language: en-US,en;q=0.8rn'
                        . 'Accept-Encoding: gzip,deflate,sdchrn'
                        . 'Accept-Charset:UTF-8,*;q=0.5rn'
                        . 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:19.0) Gecko/20100101 Firefox/19.0 FirePHP/0.4rn',
        ],
    ];

    $context = \stream_context_create($opts);
    $content = \file_get_contents($url, false, $context);

    foreach ($http_response_header as $c => $h) {
        if (\stristr($h, 'content-encoding') and \stristr($h, 'gzip')) {
            $content = \gzinflate(\substr($content, 10, -8));
        }
    }

    return $content;
}

function get_exception_message(\Throwable $e, $file = null, $line = null)
{
    $add = '';

    if ($file) {
        $add = ' throw ' . $file . ($line ? ' on line ' . $line : '');
    }

    return $e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine() . $add;
}

function classname_to_filepath(string $className, array $maps = [])
{
    $parts = explode('\\', $className);
    $j     = count($parts);

    if ($j > 1) {
        for ($i = 0; $i < $j; --$j) {
            $partKey = \implode('\\', \array_slice($parts, 0, $j)) . '\\';

            if (true === isset($maps[$partKey])) {
                $file = \preg_replace('#^' . \preg_quote($partKey) . '#', $maps[$partKey], $className);

                return \str_replace('\\', '/', $file) . (false === \strpos($file, '.php') ? '.php' : '');
            }
        }
    } else {
        if (true === isset($maps[$className . '\\'])) {
            return $maps[$className . '\\'] . \str_replace('\\', '/', $className) . (false === \strpos($className, '.php') ? '.php' : '');
        }
    }

    return null;
}

function getQueystring(string $append = '?') : string
{
    if (true === isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
        return $append . $_SERVER['QUERY_STRING'];
    }

    return '';
}

function getQs(string $append = '?') : string
{
    return \Limepie\getQueystring($append);
}

function qs(string $append = '?') : string
{
    return \Limepie\getQueystring($append);
}

function str_replace_first($search, $replace, $subject)
{
    $pos = \strpos($subject, $search);

    if (false !== $pos) {
        return \substr_replace($subject, $replace, $pos, \strlen($search));
    }

    return $subject;
}

function parse_str($string)
{
    \parse_str($string, $array);

    return $array;
}

function rawurlencode($url)
{
    if (false === \strpos($url, \rawurlencode('https://'))) {
        $url = \rawurlencode($url);
    }

    return $url;
}

function urlencode($url)
{
    if (false === \strpos($url, \urlencode('https://'))) {
        $url = \urlencode($url);
    }

    return $url;
}

function explode(string $separator, string $string, int $limit = \PHP_INT_MAX)
{
    return \array_map('trim', \explode($separator, $string, $limit));
}

function clean_str($string)
{
    return \str_replace([
        '[]', '][', '[', ']',
    ], [
        '', '-', '-', '-',
    ], $string);
}

function encrypt($str, $key)
{
    return \base58_encode(\openssl_encrypt((string) $str, 'aes-256-cbc', (string) $key, \OPENSSL_RAW_DATA, \str_repeat(\chr(0), 16)));
}

function decrypt($encrypt, $key)
{
    return \openssl_decrypt(\base58_decode($encrypt), 'aes-256-cbc', (string) $key, \OPENSSL_RAW_DATA, \str_repeat(\chr(0), 16));
}

function shield(mixed $data, ?string $key = null) : string
{
    if (null === $key) {
        $key = Environment::get('salt');
    }

    $payload = \serialize([
        \is_array($data) ? $data : (string) $data,
        \time(),
    ]);

    $nonce = \random_bytes(16);

    return \base64_encode(
        $nonce
        . \openssl_encrypt(
            $payload,
            'aes-128-gcm',
            \hash('sha256', $key, true),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        )
        . $tag
    );
}

function unshield(string $encoded, ?string $key = null, int $expireDays = 365) : mixed
{
    if (null === $key) {
        $key = Environment::get('salt');
    }

    $data = \base64_decode($encoded, true);

    if (false === $data) {
        throw new \RuntimeException('Invalid data format');
    }

    $nonce      = \substr($data, 0, 16);
    $tag        = \substr($data, -16);
    $ciphertext = \substr($data, 16, -16);

    $decrypted = \openssl_decrypt(
        $ciphertext,
        'aes-128-gcm',
        \hash('sha256', $key, true),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag
    );

    if (false === $decrypted) {
        throw new \RuntimeException('Decryption failed or data tampered');
    }

    [$data, $time] = unserialize($decrypted);

    if (\time() - $time > $expireDays * 86400) {
        throw new \RuntimeException('Data has expired');
    }

    return $data;
}

function get_max_number($max = 0)
{
    if ($max) {
        return $max;
    }

    return 2147483647;
}

// https://gist.github.com/Rodrigo54/93169db48194d470188f
function minify_js($input)
{
    if ('' === \trim($input)) {
        return $input;
    }

    return \preg_replace(
        [
            // Remove comment(s)
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove the last semicolon
            '#;+\}#',
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // --ibid. From `foo['bar']` to `foo.bar`
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i',
        ],
        [
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3',
        ],
        $input// \str_replace('"', '&quot;', $input)
    );
}

function add_string($string, $glue = ' ', $start = '', $end = '')
{
    return $string ? $glue . $start . $string . $end : '';
}

function append_string($string, $glue = ' ', $append = '')
{
    return $string . ($append ? $glue . $append : '');
}

function is_serialized_string($data, $strict = true)
{
    // if it isn't a string, it isn't serialized.
    if (!\is_string($data)) {
        return false;
    }
    $data = \trim($data);

    if ('N;' == $data) {
        return true;
    }

    if (\strlen($data) < 4) {
        return false;
    }

    if (':' !== $data[1]) {
        return false;
    }

    if ($strict) {
        $lastc = \substr($data, -1);

        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = \strpos($data, ';');
        $brace     = \strpos($data, '}');

        // Either ; or } must exist.
        if (false === $semicolon && false === $brace) {
            return false;
        }

        // But neither must be in the first X characters.
        if (false !== $semicolon && $semicolon < 3) {
            return false;
        }

        if (false !== $brace && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];

    switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== \substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === \strpos($data, '"')) {
                return false;
            }
            // or else fall through
            // no break
        case 'a':
        case 'O':
            return (bool) \preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';

            return (bool) \preg_match("/^{$token}:[0-9.E-]+;{$end}/", $data);
    }

    return false;
}

function sort_seq($a, $b)
{
    return $a['seq'] - $b['seq'];
}

function get_redirect_code($code = 301)
{
    if (\Limepie\is_ajax()) {
        return 201;
    }

    return $code;
}

/**
 * GET 파라미터로 주어진 return_url을 디코딩하고, URL 인코딩된 쿼리 스트링을 반환합니다.
 *
 * @return string URL 인코딩된 return_url 쿼리 스트링
 */
function build_qs_return_url()
{
    // get_return_url 함수를 사용하여 URL을 가져옵니다.
    $urlRequestUrl = get_return_url();

    if ($urlRequestUrl) {
        return '?return_url=' . rawurlencode($urlRequestUrl);
    }

    return '';
}

/**
 * 특정 규칙에 따라 return_url을 디코딩하고, URL 인코딩된 쿼리 스트링을 반환합니다.
 *
 * @param string $default 기본 경로, return_url이 없을 때 사용됩니다
 *
 * @return string URL 인코딩된 return_url 쿼리 스트링
 */
function build_qs_return_url_by_rule($default = '/')
{
    // get_return_url_by_rule 함수를 사용하여 URL을 가져옵니다.
    $urlRequestUrl = get_return_url_by_rule($default);

    if ($urlRequestUrl) {
        return '?return_url=' . rawurlencode($urlRequestUrl);
    }

    return '';
}

/**
 * 현재 URL을 기준으로 return_url 파라미터를 생성합니다.
 *
 * @return string URL 인코딩된 return_url 쿼리 스트링
 */
function build_qs_current_return_url()
{
    // get_current_url_path 함수를 사용하여 현재 URL을 가져옵니다.
    return build_qs_return_url_by_rule(get_current_url_path());
}

/**
 * GET 파라미터로 주어진 return_url을 디코딩하고, 경로를 반환합니다.
 *
 * @return string 디코딩된 return_url 경로
 */
function get_return_url()
{
    if (isset($_GET['return_url'])) {
        return \rawurldecode($_GET['return_url']);
    }

    return '';
}

/**
 * 특정 규칙에 따라 return_url을 디코딩하고, 경로를 반환합니다.
 *
 * @param string $default 기본 경로, return_url이 없을 때 사용됩니다
 *
 * @return string 디코딩된 return_url 경로
 */
function get_return_url_by_rule($default = '/')
{
    if (isset($_GET['return_url'])) {
        return \rawurldecode($_GET['return_url']);
    }

    if ($default) {
        return \rawurldecode($default);
    }

    return get_current_url();
}

/**
 * 현재 URL의 경로를 가져와 반환합니다.
 *
 * @return string 현재 URL 경로
 */
function get_current_url_path()
{
    // 서버 변수로부터 현재 URL 경로를 가져옵니다.
    return \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

function get_current_url()
{
    // 서버 변수로부터 현재 URL 경로를 가져옵니다.
    return $_SERVER['REQUEST_URI'];
}

function build_qs_return_current_url()
{
    // get_return_url 함수를 사용하여 URL을 가져옵니다.
    $urlRequestUrl = get_current_url();

    if ($urlRequestUrl) {
        return '?return_url=' . rawurlencode($urlRequestUrl);
    }

    return '';
}

function get_signin_url($returnUrl = null)
{
    if (!$returnUrl) {
        $returnUrl = get_current_url();
    }

    $path = '/user/sign/in';

    if ($returnUrl) {
        $path .= '?return_url=' . rawurlencode($returnUrl);
    }

    return $path;
}

function get_signin_url_back_step($number = 0)
{
    // number를 무조건 마이너스로
    $number = -\abs($number);

    $returnUrl = get_current_url();

    // 현재 path 가져오기
    $currentPath = \parse_url($returnUrl, PHP_URL_PATH);

    // '/'로 나누고 빈 값 제거
    $pathArray = \array_filter(explode('/', $currentPath));

    // number만큼 뒤에서 제거
    for ($i = 0; $i < \abs($number); ++$i) {
        \array_pop($pathArray);
    }

    // 다시 합치기
    $newPath = '/' . \implode('/', $pathArray);

    if (!empty($pathArray)) {
        $newPath .= '/';
    }

    // 새로운 return URL 만들기
    $returnUrl = \str_replace(\parse_url($returnUrl, PHP_URL_PATH), $newPath, $returnUrl);

    $path = '/user/sign/in';

    if ($returnUrl) {
        $path .= '?return_url=' . rawurlencode($returnUrl);
    }

    return $path;
}

function get_daum_postcode(?array $raw = null)
{
    $addr      = ''; // 주소 변수
    $extraAddr = ''; // 참고항목 변수
    $zonecode  = ''; // 우편번호

    if ($raw) {
        // 사용자가 선택한 주소 타입에 따라 해당 주소 값을 가져온다.
        if ('R' === $raw['userSelectedType']) {
            // 사용자가 도로명 주소를 선택했을 경우
            $addr = $raw['roadAddress'];
        } else {
            // 사용자가 지번 주소를 선택했을 경우(J)
            $addr = $raw['jibunAddress'];
        }

        // 사용자가 선택한 주소가 도로명 타입일때 참고항목을 조합한다.
        if ('R' === $raw['userSelectedType']) {
            // 법정동명이 있을 경우 추가한다. (법정리는 제외)
            // 법정동의 경우 마지막 문자가 "동/로/가"로 끝난다.
            if ('' !== $raw['bname'] && \preg_match('#[동|로|가]$#', $raw['bname'])) {
                $extraAddr .= $raw['bname'];
            }

            // 건물명이 있고, 공동주택일 경우 추가한다.
            if ('' !== $raw['buildingName'] && 'Y' === $raw['apartment']) {
                $extraAddr .= '' !== $extraAddr ? ', ' . $raw['buildingName'] : $raw['buildingName'];
            }

            // 표시할 참고항목이 있을 경우, 괄호까지 추가한 최종 문자열을 만든다.
            if ('' !== $extraAddr) {
                $extraAddr = ' (' . $extraAddr . ')';
            }
        }
        $zonecode = $raw['zonecode'];
    }

    return [
        $addr,
        $extraAddr,
        $zonecode,
    ];
}

// query string append
function qsa($key, $value = null)
{
    $queryString = parse_str($_SERVER['QUERY_STRING']);

    if (\is_array($key)) {
        $queryString = [...$queryString, ...$key];
    } else {
        if (null === $value) {
            unset($queryString[$key]);
        } else {
            $queryString[$key] = $value;
        }
    }
    $query = \http_build_query($queryString);

    return $query ? '?' . $query : '';
}

function circle_distance($lat1, $lon1, $lat2, $lon2)
{
    $rad = M_PI / 180;

    return \acos(\sin($lat2 * $rad) * \sin($lat1 * $rad) + \cos($lat2 * $rad) * \cos($lat1 * $rad) * \cos($lon2 * $rad - $lon1 * $rad)) * 6371; // Kilometers
}

function calculate_discount_rate($originalPrice, $currentPrice, $append = false)
{
    $percent = ($append ? '%' : '');

    // 현재가격이 원래 가격보다 클 경우에만 할인율 계산
    if ($currentPrice < $originalPrice) {
        $discount     = $originalPrice - $currentPrice;
        $discountRate = \round($discount / $originalPrice * 100);

        return $discountRate . $percent;
    }

    return '0' . $percent; // 할인율 없음
}

function trim_values($values)
{
    if (\is_array($values)) {
        foreach ($values as &$value) {
            $value = trim_values($value);
        }

        return $values;
    }

    return \trim($values);
}

function image_type($mime_type)
{
    return 'image' == \substr($mime_type, 0, 5);
}
function generator($array)
{
    foreach ($array as $value) {
        yield $value;
    }
}
function removeDirectoriesFromPath($path, $abs)
{
    // Count the number of ".." in $abs
    $upCount = \substr_count($abs, '..');

    // Split $path by "/"
    $directories = explode('/', $path);

    // Remove directories from the end of $path
    for ($i = 0; $i < $upCount; ++$i) {
        \array_pop($directories);
    }

    // Rejoin the remaining directories into a path string
    return \implode('/', $directories);
}
function generateRandomString($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string     = '';

    for ($i = 0; $i < $length; ++$i) {
        $string .= $characters[\rand(0, \strlen($characters) - 1)];
    }

    return $string;
}

function maskFirstChar($string, $mask_position = 3)
{
    // 문자열이 비어있는 경우 그대로 반환
    if (empty($string)) {
        return $string;
    }

    // 첫 번째 문자를 추출 (멀티바이트 안전)
    $firstChar = \mb_substr($string, 0, 1, 'UTF-8');

    // 가릴 자리수가 문자열 길이를 초과하는 경우
    if ($mask_position >= \mb_strlen($string, 'UTF-8')) {
        return '***'; // $string;
    }

    // 지정한 위치 이전의 문자를 모두 *로 대체하고 이후의 문자는 그대로 두기
    return \str_repeat('*', $mask_position) . \mb_substr($string, $mask_position, null, 'UTF-8');
}

function hideRandomChar($string)
{
    // 문자열이 6자리보다 짧으면 그대로 반환
    if (\mb_strlen($string, 'UTF-8') < 6) {
        return $string;
    }

    // 앞 6자리를 추출 (멀티바이트 안전)
    $prefix = \mb_substr($string, 0, 6, 'UTF-8');

    // 랜덤하게 숫자를 선택하여 해당 위치의 문자를 '*'로 대체
    $randomPosition = \rand(0, 5);
    $hiddenString   = \mb_substr($prefix, 0, $randomPosition, 'UTF-8') . '*' . \mb_substr($prefix, $randomPosition + 1, \mb_strlen($prefix, 'UTF-8') - $randomPosition - 1, 'UTF-8');

    // 가려진 문자열과 나머지 부분을 합쳐서 반환
    return $hiddenString . \mb_substr($string, 6, \mb_strlen($string, 'UTF-8') - 6, 'UTF-8');
}

function _get($name, $default = null)
{
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

function _getbool($name)
{
    return isset($_GET[$name]) ? true : false;
}

// 실수를 분수형태로 변경하기
function convertToFraction($number)
{
    $epsilon = 1.0e-6; // 정밀도

    if (1 == $number) {
        return 1;
    }

    $numerator   = 1;
    $denominator = 1;

    while (\abs($number - ($numerator / $denominator)) > $epsilon) {
        if ($number > ($numerator / $denominator)) {
            ++$numerator;
        } else {
            ++$denominator;
        }
    }

    return $numerator . '/' . $denominator;
}

function splitSeparator($inputString, $separator = ',')
{
    // 마지막 쉼표(,)로 문자열을 분할
    $parts = explode($separator, $inputString);

    // 배열의 마지막 요소 제거
    $lastPart = \array_pop($parts);

    // 이름과 수량 추출
    $nameAndQuantity = \implode($separator, $parts);

    // 이름과 수량 반환
    $name     = \trim($nameAndQuantity);
    $quantity = \trim($lastPart);

    return ['name' => $name, 'quantity' => $quantity];
}

// 예제 사용
// $exampleString = "payple|billing|card";
// echo str_folder_name($exampleString, '|', '/', true); // 출력: Payple/Billing/Card
function str_folder_name($inputString, $originalDelimiter = '|', $newDelimiter = '/', $capitalizeFirstLetter = true)
{
    $processedString = \str_replace($originalDelimiter, $newDelimiter, $inputString);

    if ($capitalizeFirstLetter) {
        $words          = explode($newDelimiter, $processedString);
        $processedWords = \array_map(function ($word) {
            return \ucfirst(\strtolower($word));
        }, $words);
        $processedString = \implode($newDelimiter, $processedWords);
    }

    return $processedString;
}

function add_https($url)
{
    if ($url) {
        // URL에 "http://" 또는 "https://"가 포함되어 있는지 확인합니다.
        if (!\preg_match('~^(?:f|ht)tps?://~i', $url)) {
            // "http://" 또는 "https://"가 없는 경우 "https://"를 추가합니다.
            $url = 'https://' . $url;
        }

        return $url;
    }
}

function deleteDirectory($dir)
{
    if (!\file_exists($dir)) {
        return true;
    }

    if (!\is_dir($dir)) {
        return \unlink($dir);
    }

    foreach (\scandir($dir) as $item) {
        if ('.' == $item || '..' == $item) {
            continue;
        }

        if (!\Limepie\deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return \rmdir($dir);
}

function json_validate($body)
{
    if (\function_exists('json_validate')) {
        return \json_validate($body);
    }
    // JSON 문자열을 디코드하여 오류가 발생하는지 확인합니다.
    \json_decode($body);

    return JSON_ERROR_NONE === \json_last_error();
}

function open_tag($tagName, $nonce = null)
{
    $tag = '<' . $tagName;

    if ($nonce) {
        $tag .= ' nonce="' . $nonce . '"';
    } elseif ($_SESSION['nonce'] ?? null) {
        $tag .= ' nonce="' . $_SESSION['nonce'] . '"';
    }

    $tag .= '>';

    return $tag;
}

function close_tag($tagName)
{
    return '</' . $tagName . '>';
}

function open_style_tag($nonce = null)
{
    return open_tag('style', $nonce);
}

function close_style_tag()
{
    return close_tag('style');
}

function open_script_tag($nonce = null)
{
    return open_tag('script', $nonce);
}

function close_script_tag()
{
    return close_tag('script');
}

function hx_target()
{
    return $_SERVER['HTTP_HX_TARGET'] ?? 'html';
}

function is_hx_request()
{
    if (\Limepie\is_hx_boost()) {
        return false;
    }

    return isset($_SERVER['HTTP_HX_REQUEST']) && 'true' == $_SERVER['HTTP_HX_REQUEST'];
}

function is_hx_boost()
{
    return isset($_SERVER['HTTP_HX_BOOSTED']) && 'true' == $_SERVER['HTTP_HX_BOOSTED'];
}

function is_hx_swap_paging()
{
    if (isset($_SERVER['HTTP_HX_SWAP_TYPE']) && 'paging' == $_SERVER['HTTP_HX_SWAP_TYPE']) {
        return true;
    }

    return false;
}
function is_hx_swap_change()
{
    if (isset($_SERVER['HTTP_HX_SWAP_TYPE']) && 'change' == $_SERVER['HTTP_HX_SWAP_TYPE']) {
        return true;
    }

    return false;
}
function parser_reseponce_json_message($response)
{
    if (\is_string($response)) {
        $response = \json_decode($response, true);
    }

    $message = '';

    if (!empty($response['message'])) {
        $message = $response['message'];
    }

    if (isset($response['errors']) && \is_array($response['errors'])) {
        foreach ($response['errors'] as $error) {
            if (\is_array($error) && isset($error['field'], $error['message'])) {
                $error_message = "{$error['field']}: {$error['message']}";
                $message .= ($message ? ', ' : '') . $error_message;
            }
        }
    }

    return $message;
}
function nl2br($string)
{
    return \str_replace(["\r\n", "\r", "\n"], '<br />', $string);
}

/**
 * 태그를 안전하게 텍스트로 표시하는 함수 (Escape Print)
 * HTML 태그, 인코딩된 XSS 및 다양한 주입 공격으로부터 보호합니다.
 *
 * @param mixed|string $content 출력할 콘텐츠
 * @param bool         $nl2br   줄바꿈을 <br> 태그로 변환할지 여부
 *
 * @return string 안전하게 처리된 출력 문자열
 */
function eprint($content, $nl2br = false) : string
{
    // 입력 정리 및 문자열 변환
    $content = \trim((string) $content);

    if (empty($content)) {
        return '';
    }

    // 1. 인코딩된 XSS 복원 (예: &lt;script&gt;)
    $content = \html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. 모든 위험 문자 이스케이프 (<, >, ", ', &)
    $content = \htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 3. 줄바꿈 처리 (선택 사항)
    if ($nl2br) {
        $content = \nl2br($content);
    }

    return $content;
}

// Clean Print
function cprint_tags(string $content, null|array|string $allowedTags = [], bool $sanitizeAttributes = false) : string
{
    // 설정 변수들을 정의
    $allowedDomains = [
        // YouTube 관련 도메인
        'youtube.com',
        'www.youtube.com',
        'youtu.be',           // YouTube 단축 URL
        'm.youtube.com',      // 모바일 버전
        'youtube-nocookie.com', // 프라이버시 강화 버전
        'www.youtube-nocookie.com',
        'yt3.ggpht.com',      // YouTube 썸네일 도메인
        'i.ytimg.com',        // YouTube 이미지 도메인
        'youtubei.googleapis.com', // YouTube API 도메인

        // Instagram 관련 도메인
        'instagram.com',
        'www.instagram.com',
        'm.instagram.com',     // 모바일 버전
        'instagr.am',          // 단축 도메인
        'cdninstagram.com',    // Instagram CDN
        'scontent.cdninstagram.com',
        'scontent-gmp1-1.cdninstagram.com', // 지역별 CDN
        'graph.instagram.com', // Instagram API
    ];

    // CSS 함수들
    $dangerousCssFunctions = [
        'expression',
        'behavior',
        'eval',
        'url',
        'calc',
        'attr',
    ];

    //  프로토콜들
    $dangerousProtocols = [
        'javascript',
        'data',
        'vbscript',
        'file',  // file: 프로토콜도 위험할 수 있음
    ];

    $content = \trim($content);

    if (!$content) {
        return '';
    }

    // 1. 인코딩된 XSS 우회 제거
    $content = \html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. 허용 태그 처리 - 코드 가독성 개선
    $allowedTagsStr = '';

    if (\is_array($allowedTags)) {
        $allowedTagsStr = '<' . \implode('><', \array_map('trim', $allowedTags)) . '>';
    } elseif (\is_string($allowedTags)) {
        if (\str_contains($allowedTags, ',')) {
            $allowedTagsStr = '<' . \implode('><', \array_map('trim', explode(',', $allowedTags))) . '>';
        } else {
            $allowedTagsStr = $allowedTags; // 이미 포맷된 경우
        }
    }

    // 3. strip_tags 처리
    $filtered = \strip_tags($content, $allowedTagsStr);

    // 4. 속성 필터링
    if ($sanitizeAttributes) {
        // on* 속성 제거 - 이스케이프 패턴 개선
        $filtered = \preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $filtered);

        // 위험한 프로토콜 제거 - 동적 프로토콜 목록 사용
        $protocolPattern = \implode('|', $dangerousProtocols);
        $filtered        = \preg_replace('/\s+(href|src|action|formaction)\s*=\s*([\'"]?)(' . $protocolPattern . '):[^\'">\s]*/i', ' $1=$2#', $filtered);

        // style 속성 정제 - 동적 CSS 함수 목록 사용
        $filtered = \preg_replace_callback('/\s+style\s*=\s*("[^"]*"|\'[^\']*\')/i', function ($match) use ($dangerousCssFunctions) {
            $style      = $match[1];
            $cssPattern = '(' . \implode('|', $dangerousCssFunctions) . ')\s*\(/i';
            $cleaned    = \preg_replace($cssPattern, 'removed(', $style);

            return ' style=' . $cleaned;
        }, $filtered);

        // iframe/object/embed src 도메인 화이트리스트 - 개선된 도메인 체크
        $filtered = \preg_replace_callback('/<(iframe|object|embed)([^>]*)>/i', function ($matches) use ($allowedDomains) {
            $tag   = $matches[1];
            $attrs = $matches[2];

            if (\preg_match('/\s+src\s*=\s*([\'"])(.*?)\1/i', $attrs, $srcMatch)) {
                $src       = $srcMatch[2];
                $isAllowed = false;

                // URL에서 도메인 추출 (URL 파싱)
                $domain = '';

                if (\preg_match('/^https?:\/\/([^\/]+)/', $src, $domainMatch)) {
                    $domain = \strtolower($domainMatch[1]);
                    // 서브도메인 처리 (foo.example.com -> example.com)
                    $domainParts = explode('.', $domain);

                    if (count($domainParts) > 2) {
                        $domain = \implode('.', \array_slice($domainParts, -2));
                    }
                }

                // 허용 도메인 체크
                foreach ($allowedDomains as $allowedDomain) {
                    if ($domain                                             === \strtolower($allowedDomain)
                        || \substr($domain, -(\strlen($allowedDomain) + 1)) === '.' . \strtolower($allowedDomain)) {
                        $isAllowed = true;

                        break;
                    }
                }

                if (!$isAllowed) {
                    $attrs = \preg_replace('/\s+src\s*=\s*([\'"])(.*?)\1/i', ' src=$1#$1', $attrs);
                }
            }

            return "<{$tag}{$attrs}>";
        }, $filtered);

        // 추가: SVG 및 기타 태그 속성 정제 (나쁜 속성 제거)
        $filtered = \preg_replace('/\s+(xmlns|xlink|SVG\w*)\s*=\s*([\'"])(.*?)\2/i', '', $filtered);
    }

    return $filtered;
}

// Clean Print
// <tab> 삭제됨, htmlspecialchars함
// 유저가 입력한 문자열을 강력하게 체크할 때 사용
function cprint($content, $nl2br = false) : string
{
    $content = \trim((string) $content);

    if ($content) {
        // 인코딩된 XSS 복원 → 제거 → 출력 시 이스케이프
        $content = \str_replace('&nbsp;', ' ', $content);
        $content = \html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = \strip_tags($content);
        $content = \htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($nl2br) {
            $content = \nl2br($content);
        }

        return $content;
    }

    return '';
}

function mask_format(string $string, $endLength = -4) : string
{
    return \str_repeat('*', \max(\mb_strlen($string) + $endLength, 0)) . \mb_substr($string, $endLength);
}

function format_mobile($phone, $isMark = false)
{
    $phone  = \preg_replace('/[^0-9]/', '', (string) $phone);
    $length = \strlen($phone);

    $match = '$1-$2-$3';

    if (true === $isMark) {
        $match = '$1-****-$3';
    }

    switch ($length) {
        case 11:
            return \preg_replace('/([0-9]{3})([0-9]{4})([0-9]{4})/', $match, $phone);

            break;
        case 10:
            return \preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/', $match, $phone);

            break;

        default:
            return $phone;

            break;
    }
}

function format_mobile_mask($phone)
{
    $phone  = \preg_replace('/[^0-9]/', '', $phone);
    $length = \strlen($phone);

    switch ($length) {
        case 11:
            return \preg_replace('/([0-9]{3})([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])/', '$1-**$4$5-**$8$9', $phone);

            break;
        case 10:
            return \preg_replace('/([0-9]{3})([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])([0-9])/', '$1-*$3$4-**$7$8', $phone);

            break;

        default:
            return $phone;

            break;
    }
}

/**
 * debug용 print_r.
 */
function pr()
{
    $trace = \debug_backtrace()[0];
    echo '<pre xstyle="font-size:9px;font: small monospace;">';
    echo \PHP_EOL . \str_repeat('=', 100) . \PHP_EOL;
    echo 'file ' . $trace['file'] . ' line ' . $trace['line'];
    echo \PHP_EOL . \str_repeat('-', 100) . \PHP_EOL;

    if (1 === \func_num_args()) {
        $args = \func_get_arg(0);
    } else {
        $args = \func_get_args();
    }
    echo \Limepie\print_x($args);
    echo \PHP_EOL . \str_repeat('=', 100) . \PHP_EOL;
    echo '</pre>';
}

/**
 * beautify print_r.
 *
 * @param mixed $args
 *
 * @return string
 */
function print_x($args)
{
    $a = [
        'Object' . \PHP_EOL . ' \*RECURSION\*' => '#RECURSION',
        '    '                                 => '  ',
        \PHP_EOL . \PHP_EOL                    => \PHP_EOL,
        ' \('                                  => '(',
        ' \)'                                  => ')',
        '\(' . \PHP_EOL . '\s+\)'              => '()',
        'Array\s+\(\)'                         => 'Array()',
        '\s+(Array|Object)\s+\('               => ' $1(',
    ];
    $args = \htmlentities(\print_r($args, true));

    foreach ($a as $key => $val) {
        $args = \preg_replace('#' . $key . '#X', $val, $args);
    }

    return $args;
}
