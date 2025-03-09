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
    return \dgettext($domain, $string);
}
function ___($domain, $string, $a, $b)
{
    return \dngettext($domain, $string, $a, $b);
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
    return $firstPart . ' <em>' . \trim($secondPart) . '</em>';
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

function yml_parse_file($file, ?\Closure $callback = null)
{
    $filepath = \Limepie\stream_resolve_include_path($file);

    if ($filepath) {
        $basepath = \dirname($filepath);
        $spec     = \yaml_parse_file($filepath);

        $data = \Limepie\arr\refparse($spec, $basepath);

        if (true === isset($callback) && $callback) {
            return $callback($data);
        }

        return $data;
    }

    throw new Exception('"' . $file . '" file not found');
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
    $locale = Cookie::get(Cookie::getKeyStore('locale'));

    if ($locale) {
        return \explode('_', $locale)[0];
    }

    return 'ko';

    return $_COOKIE['client-language'] ?? 'ko';
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

function is_cli() : bool
{
    if (true === isset($_ENV['is_swoole']) && 1 === (int) $_ENV['is_swoole']) {
        return false;
    }

    return 'cli' === \php_sapi_name();
}

function random_uuid()
{
    return \uuid_create(\UUID_TYPE_RANDOM);
}

function uuid(int $type = \UUID_TYPE_TIME) : string
{
    return \uuid_create($type);
}

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
    $isAssoc = \Limepie\arr\is_assoc($data);

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
    if (true === isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (true === isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    if (true === isset($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    }

    if (true === isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }

    if (true === isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    }

    if (true === isset($_SERVER['HTTP_FORWARDED'])) {
        return $_SERVER['HTTP_FORWARDED'];
    }

    if (true === isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
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
    return \Limepie\arr\to_object($array);
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

// 테그를 그대로 보여줌
function bprint($content, $nl2br = false)
{
    $content = \trim((string) $content);

    if ($content) {
        if (true) {
            $content = \str_replace(['<', '>'], ['&lt;', '&gt;'], $content);
        }

        if ($nl2br) {
            $content = \nl2br($content);
        }

        return $content;
    }

    return '';
}

function cprint_tags($content, null|array|string $allowTags = null)
{
    $content = \trim((string) $content);

    if ($content) {
        return \strip_tags($content, $allowTags);
    }

    return '';
}
// 테그를 삭제하고 보여줌. <테그아님>도 strip_tags에 의해 삭제됨
function cprint($content, $nl2br = false)
{
    $content = \trim((string) $content);

    if ($content) {
        $content = \strip_tags($content);

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
