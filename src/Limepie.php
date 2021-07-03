<?php declare(strict_types=1);

namespace Limepie;

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
function cprint($content, $nl2br = false)
{
    if ($content) {
        $content = \strip_tags($content);

        if ($nl2br) {
            $content = \nl2br($content);
        }

        return $content;
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
    //$value = \utf8_encode($value);
    //$value = \Limepie\repairSerializeString($value);
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

function format_mobile($phone, $isMark = false)
{
    $phone  = \preg_replace('/[^0-9]/', '', $phone);
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
function html_encode(array $in) : string
{
    if (0 < \count($in)) {
        $t = '<table border=1 cellspacing="0" cellpadding="0">';

        foreach ($in as $key => $value) {
            if (true === \is_assoc($in)) {
                if (true === \is_array($value)) {
                    $t .= '<tr><td>' . $key . '</td><td>' . \Limepie\html_encode($value) . '</td></tr>';
                } else {
                    $t .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            } else {
                if (true === \is_array($value)) {
                    $t .= '<tr><td>' . \Limepie\html_encode($value) . '</td></tr>';
                } else {
                    $t .= '<tr><td>' . $value . '</td></tr>';
                }
            }
        }

        return $t . '</table>';
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

                throw new \Limepie\Exception($filename . ' ' . $message);
            }

            break;

        default:
            throw new \Limepie\Exception($ext . ' not support');

            break;
    }

    return $result;
}

function ceil(float $val, int $precision = 0)
{
    $x = 1;

    for ($i = 0; $i < $precision; ++$i) {
        $x = $x * 10;
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

function array_insert_before(array $array, $key, array $new)
{
    $keys = \array_keys($array);
    $pos  = (int) \array_search($key, $keys, true);

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

function array_insert_after(array $array, $key, array $new)
{
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
        if (true === \in_array($key, ['$after', '$before', '$change', '$merge', '$remove'], true)) {
        } elseif ('$ref' === $key) {
            if (false === \is_array($value)) {
                $value = [$value];
            }
            $data = [];

            foreach ($value as $path) {
                $m       = [];
                $orgPath = $path;
                $keys    = [
                    'properties',
                ];

                if (0 === \strpos($path, '(')) {
                    if (\preg_match('#\((?P<path>.*)?\)\.(?P<key>.*)#', $path, $m)) {
                        $path = $m['path'];
                        $keys = \array_merge(\explode('.', $m['key']), $keys, );
                    }
                    //\pr($m);
                }
                //pr($keys);

                //\pr($basepath, $path);
                if ($path) {
                    if (0 !== \strpos($path, '/')) {
                        if ($basepath) {
                            $path = $basepath . '/' . $path . '';
                        }
                    }
                    $yml = \Limepie\yml_parse_file($path);

                    $yml2 = $yml;

                    foreach ($keys as $key2) {
                        if (true === isset($yml2[$key2])) {
                            $yml2 = $yml2[$key2];
                        //pr($keys, $path, $key2, $yml2);
                        } else {
                            throw new \Limepie\Exception($key2 . ' not found2');
                        }
                    }
                } else {
                    throw new \Limepie\Exception($orgPath . ' ref error');
                }

                if ($yml2) {
                    $data = \array_merge($data, $yml2);
                }
            }

            $yml = \Limepie\refparse($data, $basepath);

            $return = \array_merge($return, $yml);
        } elseif (true === \is_array($value)) {
            if (true === isset($value['lang'])) {
                if (1 === \preg_match('#\[\]$#', $key, $m)) {
                    // form 이 바뀌어야만 성립한다. 그러므로 지원하지 않음을 밝히고 form 자체를 수정하게 권고한다.
                    throw new \Limepie\Exception('[] multiple은 lang옵션을 지원하지 않습니다. group 하위로 옮기세요.');
                    // $rekey = rtrim($key, '[]');

                    // pr($key, $value);

                    // $default = $value;
                    // unset($default['lang'], $default['class'], $default['default'], $default['multiple']);

                    // $value = [
                    //     //'label'      => $value['label'],
                    //     'type'       => 'group',
                    //     'multiple'   => true,
                    //     'class'      => $value['class'] ?? '',
                    //     //'description' => $value['description'],
                    //     'properties' => [
                    //         'default' => $default,
                    //         'langs' => [
                    //             'label' => $value['label'],
                    //             'type' => 'group',
                    //             'properties' => [
                    //                 'ko' => ['label' => \Limepie\__('core', '한국어'), 'prepend' => '<i class="flag-icon flag-icon-kr"></i>'] + $default2,
                    //                 'en' => ['label' => \Limepie\__('core', '영어'), 'prepend' => '<i class="flag-icon flag-icon-us"></i>'] + $default2,
                    //                 'zh' => ['label' => \Limepie\__('core', '중국어'), 'prepend' => '<i class="flag-icon flag-icon-cn"></i>'] + $default2,
                    //                 'ja' => ['label' => \Limepie\__('core', '일본어'), 'prepend' => '<i class="flag-icon flag-icon-jp"></i>'] + $default2,

                    //             ]
                    //         ]
                    //     ],
                    // ];
                    //unset($value['lang']);
                    //pr($value);
                    $return[$key] = \Limepie\refparse($value, $basepath);
                // if ('append' === $value['lang']) {
                    //     $return[$key] = \Limepie\refparse($value, $basepath);
                    // }
                    // $default = $value;
                    // unset($default['lang'], $default['class'], $default['description'], $default['default']);
                    // $default2 = $default;

                    // if ('append' === $value['lang']) {
                    //     $default2['rules']['required'] = false;
                    // }
                    // $value = [
                    //     'label'      => $value['label'],
                    //     'type'       => 'group',
                    //     'class'      => $value['class'] ?? '',
                    //     'properties' => [
                    //         'ko' => ['label' => \Limepie\__('core', '한국어'), 'prepend' => '<i class="flag-icon flag-icon-kr"></i>'] + $default2,
                    //         'en' => ['label' => \Limepie\__('core', '영어'), 'prepend' => '<i class="flag-icon flag-icon-us"></i>'] + $default2,
                    //         'zh' => ['label' => \Limepie\__('core', '중국어'), 'prepend' => '<i class="flag-icon flag-icon-cn"></i>'] + $default2,
                    //         'ja' => ['label' => \Limepie\__('core', '일본어'), 'prepend' => '<i class="flag-icon flag-icon-jp"></i>'] + $default2,
                    //     ],
                    // ];
                    // $return[$key . '_langs'] = \Limepie\refparse($value, $basepath);
                    // pr($return);
                } else {
                    if ('append' === $value['lang']) {
                        $return[$key] = \Limepie\refparse($value, $basepath);
                    }
                    $default = $value;
                    unset($default['lang'], $default['class'],$default['style'], $default['description'], $default['default']);
                    $default2 = $default;

                    if ('append' === $value['lang']) {
                        $default2['rules']['required'] = false;
                    }
                    //unset($default2['label']);

                    if (true === \is_array($value['label'])) {
                        // TODO: 라벨이 배열일 경우 랭귀지 팩이 포함되어 있다. 이경우 배열 전체를 루프돌면서 언어팩이라는 글짜를 언어별로 추가해줘야 한다. 지금은 랭귀지팩의 특정언어를 선택해서 가져올수 없으므로 개발을 중단하고 현재의 언어에 대해서만 처리하는 형태로 완료한다.

                        // foreach($value['label'] as $langKey => &$langValue) {
                        //     $langValue .= ' - '.getLang....
                        // }

                        $label = $value['label'][\Limepie\get_language()] ?? '';
                    } else {
                        $label = $value['label'];
                    }

                    $value = [
                        'label'      => ($label ?? '') . ' - ' . \Limepie\__('core', '언어팩'),
                        'type'       => 'group',
                        'class'      => $value['class'] ?? '',
                        'properties' => [
                            'ko' => ['label' => \Limepie\__('core', '한국어'), 'prepend' => '<i class="flag-icon flag-icon-kr"></i>'] + $default2,
                            'en' => ['label' => \Limepie\__('core', '영어'), 'prepend' => '<i class="flag-icon flag-icon-us"></i>'] + $default2,
                            'zh' => ['label' => \Limepie\__('core', '중국어'), 'prepend' => '<i class="flag-icon flag-icon-cn"></i>'] + $default2,
                            'ja' => ['label' => \Limepie\__('core', '일본어'), 'prepend' => '<i class="flag-icon flag-icon-jp"></i>'] + $default2,
                        ],
                    ];
                    $return[$key . '_langs'] = \Limepie\refparse($value, $basepath);
                }
            } else {
                $return[$key] = \Limepie\refparse($value, $basepath);
            }
        } else {
            $return[$key] = $value;
        }

        if ('$after' === $key) {
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
                    $return[$k] = \Limepie\refparse(\Limepie\array_merge_deep($return[$k], $v));
                } else {
                    throw new \Limepie\Exception($key . ': Undefined array key "' . $k . '"');
                }
            }
        } elseif ('$merge' === $key) {
            foreach ($value as $k => $v) {
                if (true === isset($return[$k])) {
                    $return[$k] = \Limepie\refparse(\Limepie\array_merge_deep($return[$k], $v));
                } else {
                    throw new \Limepie\Exception($key . ': Undefined array key "' . $k . '"');
                }
            }
        } elseif ('$remove' === $key) {
            foreach ($value as $k) {
                unset($return[$k]);
            }
        }
    }

    return $return;
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
                true === isset($result[$key])
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
function yml_parse_file($file, \Closure $callback = null)
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

    throw new \Limepie\Exception('"' . $file . '" file not found');
}
/**
 * recursive array를 unique key로 merge.
 *
 * @param array $array1 초기 배열
 * @param array $array2 병합할 배열
 */
function array_merge_recursive_distinct(array $array1, array $array2) : array
{
    $merged = $array1;

    foreach ($array2 as $key => $value) {
        if (
            true === \is_array($value)
            && true === isset($merged[$key])
            && true === \is_array($merged[$key])
        ) {
            $merged[$key] = \Limepie\array_merge_recursive_distinct($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
}

function array_key_flatten($array)
{
    //if (!isset($array) || !\is_array($array)) {
    $keys = [];
    //}

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
    //if (!isset($array) || !\is_array($array)) {
    $values = [];
    //}

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

function array_mix(array $a, array $b) : array
{
    if (2 < \func_num_args()) {
        $args = \func_get_args();
        $base = \array_shift($args);

        foreach ($args as $arg) {
            $base = \Limepie\array_merge_recursive_distinct($base, $arg);
        }

        return $base;
    }

    return \Limepie\array_merge_recursive_distinct($a, $b);
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
    if (true === \is_string($enddate)) {
        $enddate = \strtotime($enddate);
    }
    $timediffer = $enddate - \time();
    $day        = \floor(($timediffer) / (60 * 60 * 24));
    $hour       = \floor(($timediffer - ($day * 60 * 60 * 24)) / (60 * 60));
    $minute     = \floor(($timediffer - ($day * 60 * 60 * 24) - ($hour * 60 * 60)) / (60));
    $second     = $timediffer - ($day * 60 * 60 * 24) - ($hour * 60 * 60) - ($minute * 60);

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
 * @param $bytes
 * @param $decimals
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
    $date = \DateTime::createFromFormat('U.u', $float);
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
            true === isset($array['name'])
            && true === isset($array['type'])
            //&& true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            return true;
        }

        if (true === $isMulti) {
            foreach ($array as $file) {
                if (
                    true === \is_array($file)
                    && true === isset($file['name'])
                    && true === isset($file['type'])
                    //&& true === isset($file['tmp_name'])
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
    $locale = \Limepie\Cookie::get(\Limepie\Cookie::getKeyStore('locale'));

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
                    throw new \Limepie\Exception('cannot create asserts directory <b>' . $createPath . '</b>');
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
                //$tmp[] = \array_merge($resultItem, [$propertyValue]);
                //$tmp[] = $resultItem + array($propertyKey => $propertyValue);
            }
        }
        $result = $tmp;
    }

    return $result;
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
        $bytes = \md5(\mt_rand());
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
    return \str_replace(['(', ')'], ['_(', '_)'], \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $word)));
}

function camelize($word)
{
    return \preg_replace_callback(
        '/(^|_|-)([a-zA-Z]+)/',
        function ($m) {
            return \ucfirst(\strtolower("{$m[2]}"));
        },
        $word
    );
}

function array_extract(
    array | object | null $arrays = [],
    array | string $key = [],
    $index = null
)
{
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
                $result = $result + \Limepie\file_array_flatten($value, $newPrefix);
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
            throw new \Limepie\Exception('not found key');
        }
    }
    $d = $value;

    return $data;
}

function array_flatten_remove($data, $flattenKey)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));
    $d    = &$data;

    foreach ($keys as $key) {
        if (true === isset($d[$key])) {
            $d = &$d[$key];
        } else {
            throw new \Limepie\Exception('not found key');
        }
    }
    unset($d);

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
    $request = Di::get('request');

    return $request->getUrl();
}

function is_binary(string $string) : bool
{
    if (!\ctype_print($string)) {
        return true;
    }

    return false;
}

function decimal($number) : float
{
    // $tmp = new \Decimal\Decimal((string) $number);

    // return $tmp->trim();

    if (0 < \strlen((string) $number)) {
        $parts  = \explode('.', (string) $number);
        $result = $parts[0];

        if (true === isset($parts[1])) {
            if ($r = \rtrim($parts[1], '0')) {
                $result .= '.' . $r;
            }
        }

        return (float) $result;
    }

    return 0;
}
function nf($number)
{
    return \Limepie\number_format($number);
}
function number_format($number, $int = 0)
{
    //$stripzero = sprintf('%g',$number);

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
    if ($array instanceof \Limepie\ArrayObject) {
        $array = $array->toArray();
    }

    return \array_pop($array);
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
        return '__-' . \str_pad((string) $seq, 11, '0', \STR_PAD_LEFT) . '-__';
    }

    return $seq;
}

function seq2key($seq)
{
    return \Limepie\seqtokey($seq);
}

function keytoseq($key)
{
    if (1 === \preg_match('#^__-([0]+)?(?P<seq>\d+)-__$#', $key, $m)) {
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

function http_build_query($data = [], $glue = '=', $separator = '&')
{
    $results = [];
    $isAssoc = \Limepie\is_assoc($data);

    foreach ($data as $k => $v) {
        if (true === \is_array($v)) {
            $results[] = $k . $glue . '[' . http_build_query($v, $glue, $separator) . ']';
        } else {
            if ($isAssoc) {
                $results[] = $k . $glue . $v;
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
        $index          = $i % $bytes;
        $newKey[$index] = $newKey[$index] ^ $key[$i];
    }

    return $newKey;
}

/**
 * \putenv('AES_SALT=1234567890abcdefg');
 * Programmatically mimic a MySQL AES_ENCRYPT() action as a way of avoiding unnecessary database calls.
 *
 * @param string     $decrypted
 * @param string     $cypher
 * @param bool       $mySqlKey
 * @param null|mixed $salt
 *
 * @return string
 */
function aes_encrypt($decrypted, $salt = null)
{
    if (null === $salt) {
        if (!($salt = \getenv('AES_SALT'))) {
            throw new \Limepie\Exception('Missing encryption salt.');
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
 * @param string     $cypher
 * @param bool       $mySqlKey
 * @param null|mixed $salt
 *
 * @return string
 */
function aes_decrypt($encrypted, $salt = null)
{
    if (null === $salt) {
        if (!($salt = \getenv('AES_SALT'))) {
            throw new \Limepie\Exception('Missing encryption salt.');
        }
    }

    $key = \Limepie\mysql_aes_key($salt);

    $cypher = 'aes-128-ecb';

    return \openssl_decrypt($encrypted, $cypher, $key, \OPENSSL_RAW_DATA);
}

function array_to_object($array)
{
    if (true === \is_array($array)) {
        return new \Limepie\ArrayObject($array);
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

function date_period($start, $end, $is_after_today = false)
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

    if (true === $is_after_today) {
        $today = new \DateTime();

        if ($first < $today) {
            $first = $today;
        }
    }

    return new \DatePeriod(
        $first->setTime(0, 0),
        new \DateInterval('P1D'),
        $last->setTime(0, 0)//->modify('+1 day') // include end date
    );
}
function date_period2($start, $end, $is_after_today = false)
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

    if (true === $is_after_today) {
        $today = new \DateTime();

        if ($first < $today) {
            $first = $today;
        }
    }

    return new \DatePeriod(
        $first->setTime(0, 0),
        new \DateInterval('P1D'),
        $last->setTime(0, 0)->modify('+1 day') // include end date
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

function get_tree(array $data = []) : array{
    $permalink = new \Limepie\Menu('', '');

    foreach ($data as $row) {
        $permalink->addSeq($row['name'], $row['current_seq'], '', $row['parent_seq'], ['seq' => $row['seq']]);
    }

    return $permalink->get(0);
}

function get_tree_item(array $data = []) : array
{
    $permalink = new \Limepie\Menu('', '');

    foreach ($data as $row) {
        $permalink->addSeq($row['name'], $row['current_seq'], '', $row['parent_seq'], ['seq' => $row['seq']]);
    }

    $iterator = new \RecursiveIteratorIterator(
        new \Limepie\RecursiveIterator\AdjacencyList($permalink->menu),
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
