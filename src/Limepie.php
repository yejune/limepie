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
function safe_input_value($string = '')
{
    // input tag에는 따옴표가 문제가 될수 있다.
    return \str_replace(['"', "'"], '', $string);
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

function cprint_tags($content, null|array|string $allowTags = null)
{
    $content = \trim((string) $content);

    if ($content) {
        return \strip_tags($content, $allowTags);
    }

    return '';
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
function get_yoil($i)
{
    // 월요일이 0부터 시작
    $week = ['월', '화', '수', '목', '금', '토', '일'];

    return $week[$i];
}
function date_format(string $date, $format)
{
    $time = \strtotime($date);

    $week   = ['일', '월', '화', '수', '목', '금', '토'];
    $yoil   = $week[\date('w', $time)];
    $format = \str_replace('w', $yoil, $format);

    $week   = ['AM' => '오전', 'PM' => '오후'];
    $yoil   = $week[\date('A', $time)];
    $format = \str_replace('A', $yoil, $format);

    $hour = \date('H', $time);

    if ($hour > 12) {
        $hour -= 12;
    }
    $format = \str_replace('h', (string) $hour, $format);

    return (new \DateTime($date))->format($format);

    return \date($format, $time);
}

function date($date)
{
    $format = 'Y년 m월 d일 A h:i';
    $date   = \str_replace(['AM', 'PM'], ['오전', '오후'], \date($format, \strtotime($date)));

    if (false !== \stripos($date, '오전 12:00')) {
        $date = \str_replace('오전 12:00', '자정', $date);
    } elseif (false !== \stripos($date, '오전 12')) {
        $date = \str_replace('오전 12', '00', $date);
    } elseif (false !== \stripos($date, '오후 12:00')) {
        $date = \str_replace('오후 12:00', '정오', $date);
    } elseif (false !== \stripos($date, '오후 12')) {
        $date = \str_replace('오후 12', '낮 12', $date);
    }

    return $date;
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

/**
 * 배열을 html table로 반환.
 *
 * @param mixed $in
 */
function array_to_html(array $in) : string
{
    if (0 < \count($in)) {
        $t = '<div class="table-responsive"><table  class="table table-sm table-bordered table-gray-bordered"><tbody>';

        foreach ($in as $key => $value) {
            if (true === \Limepie\is_assoc($in)) {
                if (true === \is_array($value)) {
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . \Limepie\array_to_html($value) . '</td></tr>';
                } else {
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . $value . '</td></tr>';
                }
            } else {
                if (true === \is_array($value)) {
                    $t .= '<tr class="bg-soft-primary"><td>' . \Limepie\array_to_html($value) . '</td></tr>';
                } else {
                    $t .= '<tr class="bg-soft-primary"><td>' . $value . '</td></tr>';
                }
            }
        }

        return $t . '</tbody></table></div>';
    }

    return '';
}

/**
 * 배열의 키가 숫자가 아닌 경우를 판별.
 *
 * @param array $array
 *
 * @return bool
 */
function is_assoc($array)
{
    if (true === \is_array($array)) {
        $keys = \array_keys($array);

        return \array_keys($keys) !== $keys;
    }

    return false;
}

function array_is_list(array $array) : bool
{
    if (empty($array)) {
        return true;
    }

    $current_key = 0;

    foreach ($array as $key => $noop) {
        if ($key !== $current_key) {
            return false;
        }
        ++$current_key;
    }

    return true;
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

function array_percent(array $numbers, $precision = 0)
{
    $result = [];
    $total  = \array_sum($numbers);

    foreach ($numbers as $key => $number) {
        $result[$key] = \Limepie\ceil(($number / $total) * 100, $precision);
    }

    $sum = \array_sum($result);

    if (100 !== $sum) {
        $maxKeys             = \array_keys($result, \max($result), true);
        $result[$maxKeys[0]] = 100 - ($sum - \max($result));
    }

    return $result;
}

function array_insert_before(array $array, $key, array|string $new)
{
    if (false === \is_array($new)) {
        $new = [$new];
    }
    $keys = \array_keys($array);
    $pos  = (int) \array_search($key, $keys, true);

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

function array_insert_after(array $array, $key, array|string $new)
{
    if (false === \is_array($new)) {
        $new = [$new];
    }

    $keys  = \array_keys($array);
    $index = (int) \array_search($key, $keys, true);
    $pos   = false === $index ? \count($array) : $index + 1;

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

// function array_insert_before($key,&$array,$new_key,$new_value='NA'){
//     if(array_key_exists($key,$array)){
//         $new = array();
//         foreach($array as $k=>$value){
//             if($k === $key){
//                 $new[$new_key] = $new_value;
//             }
//             $new[$k] = $value;
//         }
//         return $new;
//     }
//     return false;
// }

// function array_insert_after($key,&$array,$new_key,$new_value='NA'){
//     if(array_key_exists($key,$array)){
//         $new = array();
//         foreach($array as $k=>$value){
//             $new[$k] = $value;
//             if($k === $key){
//                 $new[$new_key] = $new_value;
//             }
//         }
//         return $new;
//     }
//     return false;
// }

function refparse($arr = [], $basepath = '') : array
{
    $return = [];

    foreach ($arr ?? [] as $key => $value) {
        if ('$ref' === $key) {
            if (false === \is_array($value)) {
                $value = [$value];
            }

            $data = [];

            foreach ($value as $path) {
                // $m             = [];
                $orgPath    = $path;
                $detectKeys = [
                    'properties',
                ];

                if (0 === \strpos($path, '(')) {
                    throw new Exception($orgPath . ' ref error');

                    if (\preg_match('#\((?P<path>.*)?\)\.(?P<keys>.*)#', $path, $m)) {
                        $path       = $m['path'];
                        $detectKeys = \array_merge(\explode('.', $m['keys']), $detectKeys);
                    }
                }

                if ($path) {
                    if (0 !== \strpos($path, '/')) {
                        if ($basepath) {
                            $path = $basepath . '/' . $path . '';
                        }
                    }
                    $yml = \Limepie\yml_parse_file($path);

                    $referenceData = $yml;

                    foreach ($detectKeys as $detectKey) {
                        if (true === isset($referenceData[$detectKey])) {
                            $referenceData = $referenceData[$detectKey];
                        } else {
                            throw new Exception($detectKey . ' not found2');
                        }
                    }

                    if ($referenceData) {
                        $data    = \array_merge($data, $referenceData);
                        $newData = \Limepie\refparse($data, $basepath);

                        $return = \array_merge($return, $newData);
                    }
                } else {
                    throw new Exception($orgPath . ' ref error');
                }
            }
        } elseif ('$after' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\array_insert_after($return, $k, $v1);
                }
            }
        } elseif ('$before' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\array_insert_before($return, $k, $v1);
                }
            }
        } elseif ('$change' === $key) {
            foreach ($value as $k => $v) {
                if (true === isset($return[$k])) {
                    $return[$k] = \Limepie\array_merge_deep($return[$k], $v);
                } else {
                    throw new Exception($key . ': Undefined array key "' . $k . '"');
                }
            }
        } elseif ('$merge' === $key) {
            foreach ($value as $k => $v) {
                if (true === isset($return[$k])) {
                    $return[$k] = \Limepie\array_merge_deep($return[$k], $v);
                } else {
                    throw new Exception($key . ': Undefined array key "' . $k . '"');
                }
            }
        } elseif ('$remove' === $key) {
            if (true === \is_array($value)) {
                foreach ($value as $k => $v) {
                    if (true === isset($return[$k])) {
                        /*
                         * $remove:
                         *     choice_yoil_price:
                         *       items:
                         *           0:
                         */
                        if (true === \is_array($v)) {
                            $return[$k] = \Limepie\array_remove($return[$k], $v);
                        } else {
                            unset($return[$k]);
                        }
                    } elseif (true === isset($return[$v])) {
                        /*
                         * $remove:
                         *     - key1
                         *     - key2
                         */
                        unset($return[$v]);
                    }
                    // $remove $change ...
                    // pr($return, $key, $k, $v);

                    // throw new \Limepie\Exception($key . ': Undefined array key "' . $k . '"');
                }
            } else {
                // pr($return[$value] ?? 'error');
                unset($return[$value]);
            }
        } else {
            if (true === \is_array($value)) {
                if (true === isset($value['lang'])) {
                    if (1 === \preg_match('#\[\]$#', $key, $m)) {
                        // form 이 바뀌어야만 성립한다. 그러므로 지원하지 않음을 밝히고 form 자체를 수정하게 권고한다.
                        throw new Exception('[] multiple은 lang옵션을 지원하지 않습니다. group 하위로 옮기세요.');
                    }

                    if ('append' === $value['lang']) {
                        $return[$key] = \Limepie\refparse($value, $basepath);
                    }
                    $default = $value;
                    unset($default['lang'], $default['class'],$default['style'], $default['description'], $default['default']);
                    $default2 = $default;

                    // append이고 langs으로 lang을 특정하지 않았을 경우
                    // rules required를 false 처리해준다.
                    // 원하지 않는 언어가 강제 required되는 상황 방지.
                    // langs를 입력했다는건 사용하겠다는 의미로 부모의 properties를 그대로 사용한다.
                    if ('append' === $value['lang'] && !isset($value['langs'])) {
                        $default2['rules']['required'] = false;
                    }

                    // unset($default2['label']);
                    if ($value['label'] ?? false) {
                        if (true === \is_array($value['label'])) {
                            // TODO: 라벨이 배열일 경우 랭귀지 팩이 포함되어 있다. 이경우 배열 전체를 루프돌면서 언어팩이라는 글짜를 언어별로 추가해줘야 한다. 지금은 랭귀지팩의 특정언어를 선택해서 가져올수 없으므로 개발을 중단하고 현재의 언어에 대해서만 처리하는 형태로 완료한다.

                            // foreach($value['label'] as $langKey => &$langValue) {
                            //     $langValue .= ' - '.getLang....
                            // }

                            $label = $value['label'][\Limepie\get_language()] ?? '';
                        } else {
                            $label = $value['label'];
                        }
                    } else {
                        $label = '';
                    }
                    $langProperties = [
                        'ko' => ['label' => \Limepie\__('core', '한국어'), 'prepend' => '<i class="fi fi-kr"></i>'] + $default2,
                        'en' => ['label' => \Limepie\__('core', '영어'), 'prepend' => '<i class="fi fi-us"></i>']  + $default2,
                        'ja' => ['label' => \Limepie\__('core', '일본어'), 'prepend' => '<i class="fi fi-jp"></i>'] + $default2,
                        'zh' => ['label' => \Limepie\__('core', '중국어'), 'prepend' => '<i class="fi fi-cn"></i>'] + $default2,
                    ];

                    if (isset($value['langs']) && $value['langs']) {
                        $newLangProperties = [];

                        if (is_assoc($value['langs'])) {
                            // 원하는 순서 배열을 정의합니다.
                            $desiredOrder = ['ko', 'ja', 'en', 'zh'];

                            // 익명 함수와 use 키워드를 사용하여 배열 정렬
                            \uksort($value['langs'], function ($a, $b) use ($desiredOrder) {
                                $posA = \array_search($a, $desiredOrder);
                                $posB = \array_search($b, $desiredOrder);

                                return $posA - $posB;
                            });

                            foreach ($value['langs'] as $langKey => $langProperty) {
                                if (isset($langProperties[$langKey])) {
                                    if ($langProperty) {
                                        unset($langProperties[$langKey]['langs']);
                                        $newLangProperties[$langKey] = array_merge_deep($langProperties[$langKey], $langProperty);
                                    } else {
                                        $newLangProperties[$langKey] = $langProperties[$langKey];
                                    }
                                } else {
                                    throw new Exception($langKey . ' 언어는 지원하지 않습니다.');
                                }
                            }
                        } else {
                            foreach ($value['langs'] as $langKey) {
                                if (isset($langProperties[$langKey])) {
                                    $newLangProperties[$langKey] = $langProperties[$langKey];
                                } else {
                                    throw new Exception($langKey . ' 언어는 지원하지 않습니다.');
                                }
                            }
                        }
                        $langProperties = $newLangProperties;
                    }
                    // pr($langProperties);

                    $value = [
                        'label'      => ($label ?? '') . ' - ' . \Limepie\__('core', '언어팩'),
                        'type'       => 'group',
                        'class'      => $value['class'] ?? '',
                        'properties' => $langProperties,
                    ];
                    $return[$key . '_langs'] = \Limepie\refparse($value, $basepath);
                } else {
                    $return[$key] = \Limepie\refparse($value, $basepath);
                }
            } else {
                $return[$key] = $value;
            }
        }
    }

    // pr($return);

    return $return;
}

function array_remove($result, $arr)
{
    foreach ($arr as $key => $value) {
        if (
            true    === isset($result[$key])
            && true === \is_array($result[$key])
            && true === \is_array($value)
        ) {
            $result[$key] = \Limepie\array_remove(
                $result[$key],
                $value,
            );
        } else {
            unset($result[$key]);
        }
    }

    return $result;
}

function array_merge_deep()
{
    $args = \func_get_args();

    return \Limepie\drupal_array_merge_deep_array($args);
}

// source : https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_array_merge_deep_array/7.x
function drupal_array_merge_deep_array($arrays)
{
    $result = [];

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            // Renumber integer keys as array_merge_recursive() does. Note that PHP
            // automatically converts array keys that are integer strings (e.g., '1')
            // to integers.
            if (true === \is_int($key)) {
                $result[] = $value;
            } elseif (
                true    === isset($result[$key])
                && true === \is_array($result[$key])
                && true === \is_array($value)
            ) {
                $result[$key] = \Limepie\drupal_array_merge_deep_array([
                    $result[$key],
                    $value,
                ]);
            } else {
                $result[$key] = $value;
            }
        }
    }

    return $result;
}
function yml_parse_file($file, ?\Closure $callback = null)
{
    $filepath = \Limepie\stream_resolve_include_path($file);

    if ($filepath) {
        $basepath = \dirname($filepath);
        $spec     = \yaml_parse_file($filepath);

        $data = \Limepie\refparse($spec, $basepath);

        if (true === isset($callback) && $callback) {
            return $callback($data);
        }

        return $data;
    }

    throw new Exception('"' . $file . '" file not found');
}

function array_key_flatten($array)
{
    // if (!isset($array) || !\is_array($array)) {
    $keys = [];
    // }

    foreach ($array as $key => $value) {
        $keys[] = $key;

        if (\is_array($value)) {
            $keys = \array_merge($keys, \Limepie\array_key_flatten($value));
        }
    }

    return $keys;
}
function array_value_flatten($array)
{
    // if (!isset($array) || !\is_array($array)) {
    $values = [];
    // }

    foreach ($array as $key => $value) {
        if (\is_array($value)) {
            $values = \array_merge($values, \Limepie\array_value_flatten($value));
        } else {
            $values[] = $value;
        }
    }

    return $values;
}

function array_flattenx($items)
{
    if (!\is_array($items)) {
        return [$items];
    }

    return \array_reduce($items, function ($carry, $item) {
        return \array_merge($carry, \array_flatten($item));
    }, []);
}

/**
 * time으로부터 지난 시간을 문자열로 반환.
 *
 * @param int|string $time  시간으로 표현가능한 문자열이나 숫자
 * @param int        $depth 표현 깊이
 */
function time_ago($time, int $depth = 3) : string
{
    if (true === \is_string($time)) {
        $time = \strtotime($time);
    }
    $time   = \time() - $time;
    $time   = (1 > $time) ? $time * -1 : $time;
    $tokens = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'min',
        1        => 'sec',
    ];
    $parts = [];

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = \floor($time / $unit);
        $parts[]       = $numberOfUnits . ' ' . $text . ((1 < $numberOfUnits) ? 's' : '');

        if (\count($parts) === $depth) {
            return \implode(' ', $parts);
        }
        $time -= ($unit * $numberOfUnits);
    }

    return \implode(' ', $parts);
}

function day_ago($time) : string
{
    if (true === \is_string($time)) {
        $time = \strtotime($time);
    }
    $time   = \time() - $time;
    $time   = (1 > $time) ? $time * -1 : $time;
    $tokens = [
        86400 => 'day',
    ];
    $parts = [];

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = \ceil($time / $unit);
        $parts[]       = $numberOfUnits; // . ' ' . $text . ((1 < $numberOfUnits) ? 's' : '');

        $time -= ($unit * $numberOfUnits);
    }

    return \implode(' ', $parts);
}

function ago($enddate, $format = '$d day $H:$i:$s')
{
    $hour_bun = 60;
    $min_cho  = 60;
    $hour_cho = $min_cho  * $hour_bun;
    $il_cho   = $hour_cho * 24;

    if (true === \is_string($enddate)) {
        $enddate = \strtotime($enddate);
    }
    $timediffer = $enddate - \time();
    $day        = \floor($timediffer / $il_cho);
    $hour       = \floor(($timediffer - ($day * $il_cho)) / $hour_cho);
    $minute     = \floor(($timediffer - ($day * $il_cho) - ($hour * $hour_cho)) / $min_cho);
    $second     = $timediffer - ($day * $il_cho) - ($hour * $hour_cho) - ($minute * $min_cho);

    if (1 === \strlen((string) $minute)) {
        $minute = '0' . $minute;
    }

    if (1 === \strlen((string) $second)) {
        $second = '0' . $second;
    }

    return $day . '일하고, ' . $hour . ':' . $minute . ':' . $second . '';
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

/**
 * formatting ISO8601MICROSENDS date.
 *
 * @param float $float microtime
 */
function iso8601micro(float $float) : string
{
    $date = \DateTime::createFromFormat('U.u', (string) $float);
    $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

    return $date->format('Y-m-d\TH:i:s.uP');
}

/**
 * env to array.
 */
function env_to_array(string $envPath) : array
{
    $variables = [];
    $lines     = \explode("\n", \trim(\file_get_contents($envPath)));

    if ($lines) {
        foreach ($lines as $line) {
            if ($line) {
                [$key, $value]   = \explode('=', $line, 2);
                $variables[$key] = \trim($value, '"\'');
            }
        }
    }

    return $variables;
}

/**
 * file 인지
 *
 * @param array $array
 * @param bool  $isMulti
 */
function is_file_array($array = [], $isMulti = false) : bool
{
    if (true === \is_array($array)) {
        if (
            true    === isset($array['name'])
            && true === isset($array['type'])
            // && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            return true;
        }

        if (true === $isMulti) {
            foreach ($array as $file) {
                if (
                    true    === \is_array($file)
                    && true === isset($file['name'])
                    && true === isset($file['type'])
                    // && true === isset($file['tmp_name'])
                    && true === isset($file['error'])
                    && true === isset($file['size'])
                ) {
                    return true;
                }
            }
        }
    }

    return false;
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

// https://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
function cartesian(array $input) : array
{
    $result = [[]];

    foreach ($input as $key => $values) {
        $append = [];

        foreach ($values as $value) {
            foreach ($result as $data) {
                $append[] = $data + [$key => $value];
            }
        }
        $result = $append;
    }

    return $result;
}

function array_cross($arrays)
{
    $result = [[]];

    foreach ($arrays as $property => $propertyValues) {
        $tmp = [];

        foreach ($result as $resultItem) {
            foreach ($propertyValues as $propertyKey => $propertyValue) {
                $tmp[] = $resultItem + [$property => $propertyValue];
                // $tmp[] = \array_merge($resultItem, [$propertyValue]);
                // $tmp[] = $resultItem + array($propertyKey => $propertyValue);
            }
        }
        $result = $tmp;
    }

    return $result;
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

function array_extract(
    null|array|object $arrays = [],
    array|string $key = [],
    $index = null
) {
    $return = [];

    foreach ($arrays ?? [] as $i => $value) {
        if (true === isset($index)) {
            if (true === \is_array($index)) {
                $tmp = $value;

                foreach ($index as $k1) {
                    $tmp = $tmp[$k1] ?? null;
                }
                $i1 = $tmp;
            } else {
                $i1 = $index;
            }
        } else {
            $i1 = $i;
        }

        if (true === \is_array($key)) {
            $tmp = $value;

            foreach ($key as $k1) {
                $tmp = $tmp[$k1] ?? null;
            }
            $return[$i1] = $tmp;
        } else {
            $return[$i1] = $value[$key] ?? null;
        }
    }

    return $return;
}

function file_array_flatten($list, $prefix = '')
{
    $result = [];

    foreach ($list as $name => $value) {
        if (true === \is_array($value)) {
            $newPrefix = ($prefix) ? $prefix . '[' . $name . ']' : $name;

            if (true === \Limepie\is_file_array($value, false)) {
                $result[$newPrefix] = $value;
            } else {
                $result += \Limepie\file_array_flatten($value, $newPrefix);
            }
        }
    }

    return $result;
}

function array_flatten_get($data, $flattenKey)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));

    foreach ($keys as $key) {
        if (true === isset($data[$key])) {
            $data = $data[$key];
        } else {
            return false;
        }
    }

    return $data;
}

function array_flatten_put($data, $flattenKey, $value)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));
    $d    = &$data;

    foreach ($keys as $key) {
        if (true === isset($d[$key])) {
            $d = &$d[$key];
        } else {
            throw new Exception('not found key');
        }
    }
    $d = $value;

    return $data;
}

function array_flatten_remove($data, $flattenKey)
{
    $items       = &$data;
    $segments    = \explode('[', \str_replace(']', '', $flattenKey));
    $lastSegment = \array_pop($segments);

    foreach ($segments as $segment) {
        if (false === isset($items[$segment]) || false === \is_array($items[$segment])) {
            continue;
        }

        $items = &$items[$segment];
    }

    unset($items[$lastSegment]);

    return $data;
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

function array_last($array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    return $array[\array_key_last($array)] ?? null;
}

function array_first($array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    return $array[\array_key_first($array)] ?? null;
}

function array_insert(&$array, $position, $insert)
{
    if (\is_int($position)) {
        \array_splice($array, $position, 0, $insert);
    } else {
        $pos   = \array_search($position, \array_keys($array), true);
        $array = \array_merge(
            \array_slice($array, 0, $pos),
            $insert,
            \array_slice($array, $pos)
        );
    }
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
    if (1 === \preg_match('#^__([0]+)?(?P<seq>\d+)__$#', $key, $m)) {
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

function cartesian_product(array $input)
{
    $result = [[]];

    foreach ($input as $key => $values) {
        $append = [];

        foreach ($result as $data) {
            foreach ($values as $value) {
                $append[] = $data + [$key => $value];
            }
        }
        $result = $append;
    }

    return $result;
}

function http_build_query($data = [], $glue = '=', $separator = '&', $encode = false)
{
    $results = [];
    $isAssoc = \Limepie\is_assoc($data);

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

function nest(array $flat, $value = []) : array
{
    if (!$flat) {
        return $value;
    }
    $key = $flat[\key($flat)];
    \array_splice($flat, 0, 1);

    return [$key => \Limepie\nest($flat, $value)];
}

function inflate($arr, $divider_char = '/')
{
    if (false === \is_array($arr)) {
        return false;
    }

    $split = '/' . \preg_quote($divider_char, '/') . '/';

    $ret = [];

    foreach ($arr as $key => $val) {
        $parts    = \preg_split($split, $key, -1, \PREG_SPLIT_NO_EMPTY);
        $leafpart = \array_pop($parts);
        $parent   = &$ret;

        foreach ($parts as $part) {
            if (false === isset($parent[$part])) {
                $parent[$part] = [];
            } elseif (false === \is_array($parent[$part])) {
                $parent[$part] = [];
            }
            $parent = &$parent[$part];
        }

        if (empty($parent[$leafpart])) {
            $parent[$leafpart] = $val;
        }
    }

    return $ret;
}

function flatten($arr, $base = '', $divider_char = '/')
{
    $ret = [];

    if (true === \is_array($arr)) {
        $index = -1;

        foreach ($arr as $k => $v) {
            ++$index;

            if (1 === \preg_match('#^__([^_]{13})__$#', $k, $m)) {
                $k = $index;
            }

            if (true === \is_array($v)) {
                $tmp_array = \Limepie\flatten($v, $base . $k . $divider_char, $divider_char);
                $ret       = \array_merge($ret, $tmp_array);
            } else {
                $ret[$base . $k] = $v;
            }
        }
    }

    return $ret;
}

function flatten_diff($arraya, $arrayb)
{
    $old = $arraya = \Limepie\flatten($arraya);
    $new = $arrayb = \Limepie\flatten($arrayb);

    $diff = [];

    foreach ($arraya as $key1 => $value1) {
        foreach ($arrayb as $key2 => $value2) {
            if ($key1 === $key2) {
                if ($value1 === $value2) {
                    unset($old[$key1], $new[$key2]);
                } else {
                    $diff[$key1] = [
                        'old' => $value1,
                        'new' => $value2,
                    ];
                    unset($old[$key1], $new[$key2]);
                }
            }
        }
    }

    if ($old) {
        foreach ($old as $key => $value) {
            $diff[$key] = [
                'old' => $value,
            ];
        }
    }

    if ($new) {
        foreach ($new as $key => $value) {
            $diff[$key] = [
                'new' => $value,
            ];
        }
    }

    return [$old, $new, $diff];
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

function array_to_object($array)
{
    if (true === \is_array($array)) {
        return new ArrayObject($array);
    }

    return $array;
}

function ato($array)
{
    return \Limepie\array_to_object($array);
}

function array_change_key_case_recursive(array $arr, int $case = \CASE_LOWER)
{
    return \array_map(function ($item) use ($case) {
        if (true === \is_array($item)) {
            $item = \Limepie\array_change_key_case_recursive($item, $case);
        }

        return $item;
    }, \array_change_key_case($arr, $case));
}

function date_period($start, $end, $after_today = false, $include_end_date = true)
{
    if ($start instanceof \DateTime) {
        $first = $start;
    } else {
        $first = new \DateTime($start);
    }

    if ($end instanceof \DateTime) {
        $last = $end;
    } else {
        $last = new \DateTime($end);
    }

    if (true === $after_today) {
        $today = new \DateTime();

        if ($first < $today) {
            $first = $today;
        }
    }

    if ($include_end_date) {
        $enddate = (clone $last)->setTime(0, 0)->modify('+1 day'); // include end date
    } else {
        $enddate = $last->setTime(0, 0);
    }

    return new \DatePeriod(
        $first->setTime(0, 0),
        new \DateInterval('P1D'),
        $enddate
    );
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

function array_reverse(array|ArrayObject $array = []) : array
{
    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (!$array) {
        return [];
    }

    return \array_reverse($array);
}

function array_value_sum(array $array) : int
{
    return \array_sum(\array_values($array));
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

function arrays_set(array|ArrayObject $array, array|\Closure|string $params)
{
    if (true === \is_string($params)) {
        $params = [$params];
    }

    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (true === \is_array($params)) {
        foreach ($array as &$data) {
            foreach ($data as $key => &$row) {
                if (false === \in_array($key, $params, true)) {
                    unset($data[$key]);
                }
            }
        }

        return $array;
    }
    $return = [];

    foreach ($array as $key => $row) {
        $return[$key] = $params($row);
    }

    return $return;
}

function arrays_unset(array|ArrayObject $array, array|string $params)
{
    if (true === \is_string($params)) {
        $params = [$params];
    }

    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (true === \is_array($params)) {
        foreach ($array as &$data) {
            foreach ($data as $key => &$row) {
                if (true === \in_array($key, $params, true)) {
                    unset($data[$key]);
                }
            }
        }

        return $array;
    }

    return null;
}

function array_set(array|ArrayObject $array, array|string $params)
{
    if (true === \is_string($params)) {
        $params = [$params];
    }

    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (true === \is_array($params)) {
        foreach ($array as $key => &$data) {
            if (false === \in_array($key, $params, true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }
}

function array_unset(array|ArrayObject $array, array|string $params)
{
    if (true === \is_string($params)) {
        $params = [$params];
    }

    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (true === \is_array($params)) {
        foreach ($array as $key => &$data) {
            if (true === \in_array($key, $params, true)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    return null;
}

function array_key_rename(array $array, $old_key, $new_key)
{
    if (false === \array_key_exists($old_key, $array)) {
        return $array;
    }

    $keys                                 = \array_keys($array);
    $keys[\array_search($old_key, $keys)] = $new_key;

    return \array_combine($keys, $array);
}

function array_cleanup(array|ArrayObject $value) : ?array
{
    if ($value instanceof ArrayObject) {
        $value = $value->attributes;
    }

    foreach ($value as $k => $v) {
        if (true === \is_array($v)) {
            $value[$k] = \Limepie\array_cleanup($v);

            if (0 == \count($value[$k])) {
                unset($value[$k]);
            }
        } elseif (0 == \strlen((string) $v)) {
            unset($value[$k]);
        }
    }

    return $value;
}

function array_cleanup2(array|ArrayObject $array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    $isNull = true;

    foreach ($array as $key => &$row) {
        if (true === \is_array($row)) {
            $row = \Limepie\array_cleanup($row);
        } elseif (true === \is_string($row) || true === \is_numeric($row)) {
            if (0 === \strlen((string) $row)) {
                $row = null;
            }
        } elseif (true === \is_object($row)) {
            // obejct는 허용
        } elseif (true === \is_null($row)) {
            // null 허용
            $row = null;
        } else {
            throw new Exception(\gettype($row) . ' not support.');
        }

        if (null !== $row) {
            $isNull = false;
        }
    }

    if ($isNull) {
        return null;
    }

    return $array;
}

function xml2array(\SimpleXMLElement $xml) : array
{
    $json = \json_encode($xml);

    return \json_decode($json, true);
}

function date_beetween(\DateTime $startDate, \DateTime $endDate, \DateTime $subject)
{
    return $subject->getTimestamp() >= $startDate->getTimestamp() && $subject->getTimestamp() <= $endDate->getTimestamp() ? true : false;
}

function date_count(\DateTime $startDate, \DateTime $endDate)
{
    return $startDate->diff($endDate)->format('%a') + 1;
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

function array_values_pick_keys($array, $key, $keepKey = false)
{
    $return = [];
    $index  = -1;

    foreach ($array as $arrayKey => $arrayValue) {
        if (true == $keepKey) {
            $innerKey = $arrayKey;
        } else {
            ++$index;
            $innerKey = $index;
        }

        if (true == \is_string($key)) {
            $return[$innerKey] = $arrayValue[$key];
        } else {
            $tmpArray = [];

            foreach ($arrayValue as $valueKey => $valueValue) {
                if (true == \in_array($valueKey, $key)) {
                    $tmpArray[$valueKey] = $valueValue;
                }
            }
            $return[$innerKey] = $tmpArray;
        }
    }

    return $return;
}

function array_pick_keys($array, $key)
{
    $return = [];

    foreach ($array as $valueKey => $valueValue) {
        if (true == \in_array($valueKey, $key)) {
            $return[$valueKey] = $valueValue;
        }
    }

    return $return;
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

function date_start_end($start, $end)
{
    if (null === $start || null === $end) {
        return null;
    }
    $start = \strtotime($start);
    $end   = \strtotime($end);

    $startHour = ' ' . \date('H:i', $start) . '';
    $endtHour  = ' ' . \date('H:i', $end) . '';

    return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('Y-m-d', $end) . $endtHour;
    $startYear = \date('Y', $start);
    $endYear   = \date('Y', $end);

    if ($startYear == $endYear) {
        $startMonth = \date('m', $start);
        $endMonth   = \date('m', $end);

        if ($startMonth == $endMonth) {
            return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('d', $end) . $endtHour;
        }

        return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('m-d', $end) . $endtHour;
    }

    return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('Y-m-d', $end) . $endtHour;
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

function array_tree1($array, $parent = 0)
{
    $ret = [];

    for ($i = 0; $i < count($array); ++$i) {
        if ($array[$i]['parent_seq'] == $parent) {
            $a = $array[$i];
            \array_splice($array, $i--, 1);
            $a['item'] = array_tree1($array, $a['current_seq']);
            $ret[]     = $a;

            continue;
        }
    }

    \usort($ret, 'sort_seq');

    return $ret;
}

function array_tree2($array, $parent = 0)
{
    $ret = [];

    foreach ($array as $i => $a) {
        // for ($i = 0; $i < \count($array); ++$i) {
        // if ($array[$i]['parent_seq'] == $parent) {
        if ($a['parent_seq'] == $parent) {
            $sub = array_tree2($array, $a['current_seq']);

            $ret[] = [$a['seq'] => $a];
            pr($a, $sub);

            // pr($ret);
            foreach ($sub as $j => $b) {
                // for ($j = 0; $j < \count($sub); ++$j) {
                // \array_unshift($sub[$j], $a);
                // pr($sub[$j] ?? []);
                $ret[] = [$a['seq'] => $a] + $b;
                pr($b);
                // pr($ret);
            }
        }
    }

    return $ret;
}

function get_redirect_code($code = 301)
{
    if (\Limepie\is_ajax()) {
        return 201;
    }

    return $code;
}

function get_encode_return_url($default = '/')
{
    if (isset($_GET['return_url'])) {
        $urlRequestUrl = \rawurldecode($_GET['return_url']);
    } elseif ($default) {
        $urlRequestUrl = \rawurldecode($default);
    } else {
        $urlRequestUrl = $_SERVER['REQUEST_URI'];
    }

    if ($urlRequestUrl) {
        return '?return_url=' . \rawurlencode($urlRequestUrl);
    }

    return '';
}

function get_return_url($default = '/')
{
    if (isset($_GET['return_url'])) {
        $urlRequestUrl = \rawurldecode($_GET['return_url']);
    } elseif ($default) {
        $urlRequestUrl = \rawurldecode($default);
    } else {
        $urlRequestUrl = $_SERVER['REQUEST_URI'];
    }

    return $urlRequestUrl;
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
    $query = http_build_query($queryString);

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
function display_dday($startDate, $endDate)
{
    // 모집 시작일과 종료일을 DateTime 객체로 변환하고 시간 부분을 제거
    $startDate = new DateTime($startDate);
    $startDate->setTime(0, 0);

    $endDate = new DateTime($endDate);
    $endDate->setTime(0, 0);

    // 오늘 날짜 DateTime 객체로 가져오고 시간 부분을 제거
    $today = new DateTime();
    $today->setTime(0, 0);

    // 종료일이 이미 지난 경우
    if ($today > $endDate) {
        return null;
    }

    // 모집 시작일까지 남은 일수 계산
    $daysUntilStart = $today->diff($startDate)->days;

    // 모집 종료일까지 남은 일수 계산
    $daysUntilEnd = $today->diff($endDate)->days;

    // 시작일이 아직 오지 않은 경우
    if ($today < $startDate) {
        return -$daysUntilStart;
    }

    // 오늘이 모집 종료일인 경우
    if ($today == $endDate) {
        return 0;
    }

    // 종료일까지 남은 일수 반환
    return $daysUntilEnd;
}

function display_dday_message($startDate, $endDate, $messages = [], $classes = [])
{
    // 내부 display_dday 함수 호출
    $days = display_dday($startDate, $endDate);

    // 기본 메시지
    $defaultMessages = [
        'ended'           => '종료',
        'starts_in'       => '%d일 후 시작',
        'ends_today'      => '오늘 %s에 종료',
        'days_left'       => '%d일 남음',
        'starts_tomorrow' => '내일 %s에 시작',
        'ends_tomorrow'   => '내일 %s에 종료',
    ];

    // 기본 클래스
    $defaultColors = [
        'ended'           => 'color-ended',
        'starts_in'       => 'color-starts-in',
        'ends_today'      => 'color-ends-today',
        'days_left'       => 'color-days-left',
        'starts_tomorrow' => 'color-starts-tomorrow',
        'ends_tomorrow'   => 'color-ends-tomorrow',
    ];

    // 사용자 정의 메시지가 있는 경우 기본 메시지를 덮어쓰기
    $messages = \array_merge($defaultMessages, $messages);

    // 사용자 정의 클래스가 있는 경우 기본 클래스를 덮어쓰기
    $classes = \array_merge($defaultColors, $classes);

    // DateTime 객체로 변환
    $startDateTime = new DateTime($startDate);
    $endDateTime   = new DateTime($endDate);

    // 시간 형식
    $startTime = $startDateTime->format('H:i');
    $endTime   = $endDateTime->format('H:i');

    // 메시지 작성
    if (\is_null($days)) {
        return \sprintf('<span class="%s">%s</span>', $classes['ended'], $messages['ended']);
    }

    if ($days < 0) {
        if (1 == \abs($days)) {
            return \sprintf('<span class="%s">%s</span>', $classes['starts_tomorrow'], \sprintf($messages['starts_tomorrow'], $startTime));
        }

        return \sprintf('<span class="%s">%s</span>', $classes['starts_in'], \sprintf($messages['starts_in'], \abs($days)));
    }

    if (0 == $days) {
        return \sprintf('<span class="%s">%s</span>', $classes['ends_today'], \sprintf($messages['ends_today'], $endTime));
    }

    if (1 == $days) {
        return \sprintf('<span class="%s">%s</span>', $classes['ends_tomorrow'], \sprintf($messages['ends_tomorrow'], $endTime));
    }

    return \sprintf('<span class="%s">%s</span>', $classes['days_left'], \sprintf($messages['days_left'], $days));
}

function get_countdown_days($recruit_start_dt, $recruit_end_dt, $recruit_announce_dt, $end_dt, $days_before_end = 2, $lang = 'ko', $selected_messages = [])
{
    $today                     = new DateTime('now');
    $recruit_start             = new DateTime($recruit_start_dt);
    $recruit_end               = new DateTime($recruit_end_dt);
    $recruit_end_x_days_before = (clone $recruit_end)->modify("-{$days_before_end} days");
    $recruit_announce          = new DateTime($recruit_announce_dt);
    $end                       = new DateTime($end_dt);

    // 언어별로 메시지 매핑
    if ($selected_messages) {
    } else {
        $messages = [
            'ko' => [
                'today_end'   => '오늘 종료',
                'days_before' => 'D-%d',
                'recruit_end' => '모집 종료',
                'announce'    => '당첨자 발표',
                'end'         => '종료',
                'scheduled'   => '진행 예정',
                'ongoing'     => '모집 중',
            ],
            'ja' => [
                'today_end'   => '今日終了',
                'days_before' => 'D-%d',
                'recruit_end' => '募集終了',
                'announce'    => '当選者発表',
                'end'         => '終了',
                'scheduled'   => '予定',
                'ongoing'     => '募集中',
            ],
            // 다른 언어에 대한 메시지 추가 가능
        ];

        // 선택된 언어에 해당하는 메시지 배열 선택
        $selected_messages = $messages[$lang];
    }

    if ($today == $recruit_end) {
        return $selected_messages['today_end'];
    }

    if ($today == $recruit_end_x_days_before) {
        return \sprintf($selected_messages['days_before'], $days_before_end);
    }

    if ($today >= $recruit_end) {
        return $selected_messages['recruit_end'];
    }

    if ($today >= $recruit_announce && $today < $end) {
        return $selected_messages['announce'];
    }

    if ($today >= $end) {
        return $selected_messages['end'];
    }

    if ($today < $recruit_start) {
        return $selected_messages['scheduled'];
    }

    return $selected_messages['ongoing'];
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

function getDayOfWeek($date)
{
    // $date는 'YYYY-MM-DD' 형식의 문자열이어야 합니다.
    $timestamp = \strtotime($date);
    $dayOfWeek = \date('w', $timestamp); // 0 (일요일)에서 6 (토요일)까지의 정수 값을 반환합니다.

    $days = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];

    return $days[$dayOfWeek];
}

function getTimeRemaining($date, $showHours = true, $showMinutes = true, $showSeconds = false)
{
    // $date는 'YYYY-MM-DD' 형식의 문자열이어야 합니다.
    $targetDate       = $date . ' 23:59:59';
    $targetTimestamp  = \strtotime($targetDate);
    $currentTimestamp = \time();

    $timeDifference = $targetTimestamp - $currentTimestamp;

    if ($timeDifference < 0) {
        return '날짜가 이미 지났습니다.';
    }

    $daysRemaining = \floor($timeDifference / 86400); // 1일은 86400초입니다.
    $timeDifference %= 86400;

    $hoursRemaining = \floor($timeDifference / 3600); // 1시간은 3600초입니다.
    $timeDifference %= 3600;

    $minutesRemaining = \floor($timeDifference / 60); // 1분은 60초입니다.
    $secondsRemaining = $timeDifference % 60;

    $result = "{$daysRemaining}일";

    if ($showHours) {
        $result .= " {$hoursRemaining}시간";
    }

    if ($showMinutes) {
        $result .= " {$minutesRemaining}분";
    }

    if ($showSeconds) {
        $result .= " {$secondsRemaining}초";
    }

    $result .= ' 남았습니다.';

    return $result;
}

// 기준일로부터 x주 간격으로 반복되는 일정의 가장 가까운 다음 일정을 계산
// 기준일이 미래 날짜인 경우 그 기준일 자체가 가장 가까운 다음 일정이 됨
// 최소 minDaysAfterToday일 이후 날짜를 반환하도록 설정
function getClosestNextEvent($baseDate, $intervalWeeks, $minDaysAfterToday = 7)
{
    if ($intervalWeeks < 1) {
        throw new \InvalidArgumentException('intervalWeeks는 1 이상의 정수여야 합니다.');
    }
    $baseDateTimestamp = \strtotime($baseDate);
    $currentTimestamp  = \time();
    $minDaysTimestamp  = \strtotime("+{$minDaysAfterToday} days", $currentTimestamp);

    if ($baseDateTimestamp >= $minDaysTimestamp) {
        return \date('Y-m-d', $baseDateTimestamp);
    }

    $nextEventTimestamp = $baseDateTimestamp;

    while ($nextEventTimestamp < $minDaysTimestamp) {
        $nextEventTimestamp = \strtotime("+{$intervalWeeks} weeks", $nextEventTimestamp);
    }

    return \date('Y-m-d', $nextEventTimestamp);
}

// 주어진 날짜에 일(day) 단위의 숫자를 더하거나 뺀 날짜를 반환
function adjustDate($date, $days)
{
    $dateTimestamp     = \strtotime($date);
    $adjustedTimestamp = \strtotime("{$days} days", $dateTimestamp);

    return \date('Y-m-d', $adjustedTimestamp);
}

function is_date_greater($input_date, $comparison_date = null)
{
    $timestamp_input = \strtotime($input_date);

    if (null === $comparison_date) {
        $timestamp_comparison = \time(); // 현재 Unix 타임스탬프를 가져옵니다.
    } else {
        $timestamp_comparison = \strtotime($comparison_date);
    }

    if ($timestamp_input > $timestamp_comparison) {
        return true;
    }

    return false;
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

/**
 * Undocumented function
 * $extracted1 = extractKeys($data['standard']['image'], ['url', 'seq']);
 * print_r($extracted1);  // 배열로 키를 전달
 * $extracted2 = extractKeys($data['standard']['image'], 'url');
 * print_r($extracted2);  // 문자열로 키를 전달.
 *
 * @param mixed $isAll
 */
function extractKeys(?array $array, array|string $keysToExtract, $isAll = true)
{
    if (null === $array) {
        return null;
    }

    $result = [];

    if (\is_string($keysToExtract)) {
        return isset($array[$keysToExtract]) ? $array[$keysToExtract] : null;
    }

    foreach ($keysToExtract as $key => $value) {
        if (\is_numeric($key)) {  // 숫자 인덱스인 경우
            $actualKey = $value;
            $aliasKey  = $value;
        } else {                 // 문자열 인덱스인 경우 (alias 사용)
            $actualKey = $key;
            $aliasKey  = $value;
        }

        if ($isAll) {
            $result[$aliasKey] = $array[$actualKey] ?? null;
        } else {
            if (isset($array[$actualKey]) && ('' !== $array[$actualKey] && null !== $array[$actualKey])) {
                $result[$aliasKey] = $array[$actualKey];
            }
        }
    }

    if ('' !== $result && !([] === $result)) {
        return $result;
    }

    return null;
}

function key_sort(&$array, $path)
{
    $paths = explode('.', $path);

    \usort($array, function ($a, $b) use ($paths) {
        $valA = $a;
        $valB = $b;

        foreach ($paths as $key) {
            if (isset($valA[$key])) {
                $valA = $valA[$key];
            } else {
                return -1; // 또는 적절한 기본값
            }

            if (isset($valB[$key])) {
                $valB = $valB[$key];
            } else {
                return 1; // 또는 적절한 기본값
            }
        }

        if ($valA == $valB) {
            return 0;
        }

        return ($valA < $valB) ? -1 : 1;
    });

    return $array;
}

/*

주어진 기준 날짜부터 현재까지의 월수에 따라 특정 가격에 할인을 적용합니다. 함수는 네 개의 매개변수를 받습니다:

$baseDate: 할인 계산의 기준이 되는 날짜입니다.
$currentPrice: 할인 전의 가격입니다.
$discountRates: 월별 할인율을 담은 연관 배열로, 각 월수에 해당하는 할인율을 정의합니다 (예: 1개월 이내 1.8% 할인).

함수는 기준 날짜로부터 현재까지의 월수를 계산하고, 이에 따라 해당 월에 적용되는 할인율을 찾아 가격에 적용합니다. 지정된 최대 월수를 초과하는 경우, 함수는 예외를 발생시켜 사용자에게 할인 기간이 초과되었음을 알립니다.


// 할인율 설정: [월 => 할인율(%)]
$discountRates = [
    1 => 1.8, // 1개월 이내
    2 => 1.2, // 2개월 이내
    3 => 0.6  // 3개월 이내
];

$maxMonths = 3; // 최대 할인 적용 가능 월

// 예시 사용
$baseDate = '2023-01-01'; // 기준 날짜 예시
$currentPrice = 10000; // 현재 가격 예시

try {
    $discountedPrice = calculateDiscountedPrice($baseDate, $currentPrice, $discountRates, $maxMonths);
    echo "할인된 가격: " . $discountedPrice;
} catch (Exception $e) {
    echo "오류: " . $e->getMessage();
}
 */
function calculateDiscountedPrice($baseDate, $currentPrice, $discountRates)
{
    // 할인율 배열이 비어있는 경우 확인
    if (empty($discountRates)) {
        return ['amount' => $currentPrice, 'discount' => 0, 'rate' => 1, 'month' => null];
    }

    $now      = new DateTime();
    $baseDate = new DateTime($baseDate);
    $interval = $now->diff($baseDate);

    $months = $interval->m + ($interval->y * 12); // 연도를 월로 변환

    // 할인 기간 초과 확인
    if ($months > \max(\array_keys($discountRates))) {
        throw new Exception('할인 기간이 초과되었습니다.');
    }

    // 할인율 적용
    foreach ($discountRates as $month => $rate) {
        if ($months <= $month) {
            $discount         = ($currentPrice * $rate) / 100;
            $discountedAmount = $currentPrice - $discount;

            return [
                'amount'   => \round($discountedAmount), // 반올림 적용
                'discount' => \round($discount), // 반올림 적용
                'month'    => $month,
                'rate'     => $rate,
            ];
        }
    }
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
    // URL에 "http://" 또는 "https://"가 포함되어 있는지 확인합니다.
    if (!\preg_match('~^(?:f|ht)tps?://~i', $url)) {
        // "http://" 또는 "https://"가 없는 경우 "https://"를 추가합니다.
        $url = 'https://' . $url;
    }

    return $url;
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

function is_hx_request()
{
    return isset($_SERVER['HTTP_HX_REQUEST']) && 'true' == $_SERVER['HTTP_HX_REQUEST'];
}

function parser_reseponce_json_message($response)
{
    if(is_string($response)) {
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
function get_datetime6_difference($datetime1, $datetime2)
{
    if (null === $datetime1 || null === $datetime2) {
        return null;
    }
    $dt1 = DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime1);
    $dt2 = DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime2);

    if (!$dt1 || !$dt2) {
        return 'Invalid datetime format';
    }

    $interval     = $dt1->diff($dt2);
    $seconds      = $interval->s;
    $minutes      = $interval->i;
    $hours        = $interval->h;
    $days         = $interval->d;
    $months       = $interval->m;
    $years        = $interval->y;
    $microseconds = \abs($dt1->format('u') - $dt2->format('u'));

    $parts = [];

    if ($years > 0) {
        $parts[] = $years . ' years';
    }

    if ($months > 0) {
        $parts[] = $months . ' months';
    }

    if ($days > 0) {
        $parts[] = $days . ' days';
    }

    if ($hours > 0) {
        $parts[] = $hours . ' hours';
    }

    if ($minutes > 0) {
        $parts[] = $minutes . ' minutes';
    }

    if ($seconds > 0 || $microseconds > 0) {
        $parts[] = \sprintf('%d.%06d seconds', $seconds, $microseconds);
    }

    return \implode(', ', $parts);
}

function get_current_datetime6()
{
    // microtime을 float 형식으로 얻기
    $microtime = \microtime(true);

    // 전체 초와 마이크로초 부분을 분리
    $parts        = \explode('.', (string) $microtime);
    $seconds      = (int) $parts[0];
    $microseconds = isset($parts[1]) ? \str_pad((string) $parts[1], 6, '0') : '000000';

    // 날짜와 시간을 포맷팅
    return \date('Y-m-d H:i:s', $seconds) . '.' . $microseconds;
}
