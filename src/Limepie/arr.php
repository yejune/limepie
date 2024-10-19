<?php

declare(strict_types=1);

namespace Limepie\arr;

use Limepie\ArrayObject;
use Limepie\Di;
use Limepie\Exception;

/**
 * 배열의 첫 번째 요소를 제거하고 그 값을 반환합니다. 키는 유지되지 않습니다.
 * 원본 배열이 수정됩니다.
 *
 * @param array &$array 대상 배열 (참조로 전달)
 *
 * @return null|mixed 첫 번째 요소의 값 또는 빈 배열일 경우 null
 */
function shift(&$array)
{
    if (!\is_array($array) || empty($array)) {
        return null;
    }
    $key   = \key($array);
    $value = \reset($array);
    unset($array[$key]);

    return $value;
}

/**
 * 배열에서 지정된 키의 값을 제거하고 반환합니다.
 * 원본 배열이 수정됩니다.
 *
 * @param array &$array 대상 배열 (참조로 전달)
 * @param mixed $key    제거할 키
 *
 * @return mixed 제거된 값 또는 키가 없을 경우 null
 */
function pull(&$array, $key)
{
    if (\array_key_exists($key, $array)) {
        $value = $array[$key];
        unset($array[$key]);

        return $value;
    }

    return null;
}

function difference($array1, $array2)
{
    // 배열의 값을 문자열로 변환
    $array1 = \array_map('strval', $array1);
    $array2 = \array_map('strval', $array2);

    // 각 배열에 고유한 요소 찾기
    $diff1 = \array_diff($array1, $array2);
    $diff2 = \array_diff($array2, $array1);

    // 결과 반환
    return [
        'only_in_first'  => \array_values($diff1),
        'only_in_second' => \array_values($diff2),
    ];
}

function is_same($array1, $array2)
{
    // 배열 크기 비교
    if (\count($array1) !== \count($array2)) {
        return false;
    }

    // 배열의 값을 문자열로 변환하고 정렬
    $sorted1 = \array_map('strval', $array1);
    $sorted2 = \array_map('strval', $array2);
    \sort($sorted1);
    \sort($sorted2);

    // 정렬된 배열 비교
    return $sorted1 === $sorted2;
}

function is_diff($array1, $array2)
{
    return !\Limepie\arr\is_same($array1, $array2);
}

// 5점이 3건, 4점이 2건, 3점이 1건이 있는 경우 로직상 가장 많은 것을 가득채우고 그것의 비례에 맞게 나머지를 비율로 채움
function get_percent_stars($reviewCounts)
{
    // 리뷰 카운트 배열: [5점 카운트, 4점 카운트, 3점 카운트, 2점 카운트, 1점 카운트]
    $totalReviews = \array_sum($reviewCounts);

    if (0 == $totalReviews) {
        return [
            'percentages' => [
                5 => 0,
                4 => 0,
                3 => 0,
                2 => 0,
                1 => 0,
            ],
            'max_group' => null,
            'max_count' => 0,
        ];
    }

    $maxCount    = \max($reviewCounts);
    $scaleFactor = 100 / $maxCount;  // 최대값을 100%로 스케일링

    $scaledCounts = \array_map(function ($count) use ($scaleFactor) {
        return \round($count * $scaleFactor, 2);
    }, $reviewCounts);

    $highestScoreIndex = \array_search($maxCount, $reviewCounts);
    $highestScoreGroup = 5 - $highestScoreIndex;

    return [
        'percentages' => [
            5 => $scaledCounts[0],
            4 => $scaledCounts[1],
            3 => $scaledCounts[2],
            2 => $scaledCounts[3],
            1 => $scaledCounts[4],
        ],
        'max_group' => $highestScoreGroup,
        'max_count' => $maxCount,
    ];
}

function replace($description, array $row = [])
{
    // preg_replace_callback 함수를 사용하여 정규표현식 패턴에 맞는 부분을 찾아 치환합니다.
    return \preg_replace_callback(
        // 정규표현식 패턴: {=로 시작하고 }로 끝나는 패턴을 찾습니다.
        // (\w+)는 하나 이상의 단어 문자(알파벳, 숫자, 언더스코어)를 캡처합니다.
        '/\{=(\w+)\}/',

        // 콜백 함수: 매치된 각 패턴에 대해 실행됩니다.
        function ($matches) use ($row) {
            // $matches[0]는 전체 매치 (예: {=created_ts})
            // $matches[1]는 첫 번째 캡처 그룹 (예: created_ts)

            // $row 배열에서 해당 키를 찾아 반환합니다.
            // 만약 키가 없다면 원래 문자열을 그대로 반환합니다.
            return $row[$matches[1]] ?? $matches[0];
        },

        // 원본 문자열
        $description
    );
}

// 숫자 배열을 퍼센트로 변환
function percent(array $numbers, $precision = 0)
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

/**
 * 배열을 html table로 반환.
 *
 * @param mixed $in
 */
function to_html(array $in) : string
{
    if (0 < \count($in)) {
        $t = '<div class="table-responsive"><table  class="table table-sm table-bordered table-gray-bordered"><tbody>';

        foreach ($in as $key => $value) {
            if (true === is_assoc($in)) {
                if (true === \is_array($value)) {
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . \Limepie\arr\to_html($value) . '</td></tr>';
                } else {
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . $value . '</td></tr>';
                }
            } else {
                if (true === \is_array($value)) {
                    $t .= '<tr class="bg-soft-primary"><td>' . \Limepie\arr\to_html($value) . '</td></tr>';
                } else {
                    $t .= '<tr class="bg-soft-primary"><td>' . $value . '</td></tr>';
                }
            }
        }

        return $t . '</tbody></table></div>';
    }

    return '';
}

// 주어진 배열이 연관 배열(associative array)인지 확인
function is_assoc($array)
{
    if (true === \is_array($array)) {
        $keys = \array_keys($array);

        return \array_keys($keys) !== $keys;
    }

    return false;
}

// 배열이 순차적인 정수 키를 가진 "리스트" 형태인지 확인
function is_list(array $array) : bool
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

function insert_before(array $array, $key, array|string $new)
{
    if (false === \is_array($new)) {
        $new = [$new];
    }
    $keys = \array_keys($array);
    $pos  = (int) \array_search($key, $keys, true);

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

function insert_after(array $array, $key, array|string $new)
{
    if (false === \is_array($new)) {
        $new = [$new];
    }

    $keys  = \array_keys($array);
    $index = (int) \array_search($key, $keys, true);
    $pos   = false === $index ? \count($array) : $index + 1;

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

// function insert_before($key,&$array,$new_key,$new_value='NA'){
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

// function insert_after($key,&$array,$new_key,$new_value='NA'){
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

function remove($result, $arr)
{
    foreach ($arr as $key => $value) {
        if (
            true    === isset($result[$key])
            && true === \is_array($result[$key])
            && true === \is_array($value)
        ) {
            $result[$key] = \Limepie\arr\remove(
                $result[$key],
                $value,
            );
        } else {
            unset($result[$key]);
        }
    }

    return $result;
}

function merge_deep()
{
    $args = \func_get_args();

    return \Limepie\arr\drupal_array_merge_deep_array($args);
}

function key_flatten($array)
{
    // if (!isset($array) || !\is_array($array)) {
    $keys = [];
    // }

    foreach ($array as $key => $value) {
        $keys[] = $key;

        if (\is_array($value)) {
            $keys = \array_merge($keys, \Limepie\arr\key_flatten($value));
        }
    }

    return $keys;
}

function value_flatten($array)
{
    // if (!isset($array) || !\is_array($array)) {
    $values = [];
    // }

    foreach ($array as $key => $value) {
        if (\is_array($value)) {
            $values = \array_merge($values, \Limepie\arr\value_flatten($value));
        } else {
            $values[] = $value;
        }
    }

    return $values;
}

function flattenx($items)
{
    if (!\is_array($items)) {
        return [$items];
    }

    return \array_reduce($items, function ($carry, $item) {
        return \array_merge($carry, \array_flatten($item));
    }, []);
}

function cross($arrays)
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

function extract(
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

function flatten_put($data, $flattenKey, $value)
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

function flatten_remove($data, $flattenKey)
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

function last($array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    return $array[\array_key_last($array)] ?? null;
}

function first($array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    return $array[\array_key_first($array)] ?? null;
}

function insert(&$array, $position, $insert)
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

function to_object($array)
{
    if (true === \is_array($array)) {
        return new ArrayObject($array);
    }

    return $array;
}
function change_key_case_recursive(array $arr, int $case = \CASE_LOWER)
{
    return \array_map(function ($item) use ($case) {
        if (true === \is_array($item)) {
            $item = \Limepie\arr\change_key_case_recursive($item, $case);
        }

        return $item;
    }, \array_change_key_case($arr, $case));
}

function reverse(array|ArrayObject $array = []) : array
{
    if (false === \is_array($array)) {
        $array = $array->toArray();
    }

    if (!$array) {
        return [];
    }

    return \array_reverse($array);
}

function value_sum(array $array) : int
{
    return \array_sum(\array_values($array));
}

function _set(array|ArrayObject $array, array|\Closure|string $params)
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

function _unset(array|ArrayObject $array, array|string $params)
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

function set(array|ArrayObject $array, array|string $params)
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

// array_unset -> delete
function delete(array|ArrayObject $array, array|string $params)
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

function key_rename(array $array, $old_key, $new_key)
{
    if (false === \array_key_exists($old_key, $array)) {
        return $array;
    }

    $keys                                 = \array_keys($array);
    $keys[\array_search($old_key, $keys)] = $new_key;

    return \array_combine($keys, $array);
}

function cleanup(array|ArrayObject $value) : ?array
{
    if ($value instanceof ArrayObject) {
        $value = $value->attributes;
    }

    foreach ($value as $k => $v) {
        if (true === \is_array($v)) {
            $value[$k] = \Limepie\arr\cleanup($v);

            if (0 == \count($value[$k])) {
                unset($value[$k]);
            }
        } elseif (0 == \strlen((string) $v)) {
            unset($value[$k]);
        }
    }

    return $value;
}

// 배열에서 특정 패턴(__(.*){13}__)을 숫자로 바꾸고, 값이 빈 배열이거나 빈 문자열('')인 경우 null로 교체
function clean($array)
{
    $transformed = [];
    $i           = -1;

    foreach ($array as $key => $value) {
        ++$i;

        if (\preg_match('/^__(.*){13}__$/', $key)) {
            $key = $i;
        }

        // 값이 배열이면 재귀적으로 처리
        if (\is_array($value)) {
            // 빈 배열은 null로 교체
            if (empty($value)) {
                $value = null;
            } else {
                $value = \Limepie\arr\clean($value);
            }
        } elseif ('' === $value) {
            // 빈 문자열을 null로 교체
            $value = null;
        }

        $transformed[$key] = $value;
    }

    return $transformed;
}

function cleanup2(array|ArrayObject $array)
{
    if ($array instanceof ArrayObject) {
        $array = $array->attributes;
    }

    $isNull = true;

    foreach ($array as $key => &$row) {
        if (true === \is_array($row)) {
            $row = \Limepie\arr\cleanup($row);
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

function values_pick_keys($array, $key, $keepKey = false)
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

function pick_keys($array, $key)
{
    $return = [];

    foreach ($array as $valueKey => $valueValue) {
        if (true == \in_array($valueKey, $key)) {
            $return[$valueKey] = $valueValue;
        }
    }

    return $return;
}

function tree1($array, $parent = 0)
{
    $ret = [];

    for ($i = 0; $i < \count($array); ++$i) {
        if ($array[$i]['parent_seq'] === $parent) {
            $a = $array[$i];
            \array_splice($array, $i--, 1);
            $a['item'] = \Limepie\arr\tree1($array, $a['current_seq']);
            $ret[]     = $a;

            continue;
        }
    }

    \usort($ret, 'sort_seq');

    return $ret;
}

function tree2($array, $parent = 0)
{
    $ret = [];

    foreach ($array as $i => $a) {
        // for ($i = 0; $i < \count($array); ++$i) {
        // if ($array[$i]['parent_seq'] == $parent) {
        if ($a['parent_seq'] == $parent) {
            $sub = \Limepie\arr\tree2($array, $a['current_seq']);

            $ret[] = [$a['seq'] => $a];
            \pr($a, $sub);

            // pr($ret);
            foreach ($sub as $j => $b) {
                // for ($j = 0; $j < \count($sub); ++$j) {
                // \array_unshift($sub[$j], $a);
                // pr($sub[$j] ?? []);
                $ret[] = [$a['seq'] => $a] + $b;
                \pr($b);
                // pr($ret);
            }
        }
    }

    return $ret;
}

function injection_key($datasets, $requiredKeys)
{
    return \array_map(function ($values) use ($requiredKeys) {
        return \array_combine(
            $requiredKeys,
            $values
        );
    }, $datasets);
}

function flatten_get($data, $flattenKey)
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

function array_rmerge($arr1, $arr2)
{
    $merged = $arr1;

    foreach ($arr2 as $key => &$value) {
        if (\is_array($value) && isset($merged[$key]) && \is_array($merged[$key])) {
            $merged[$key] = array_rmerge($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
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
                $result[$key] = \Limepie\arr\drupal_array_merge_deep_array([
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

function file_array_flatten($list, $prefix = '')
{
    $result = [];

    foreach ($list as $name => $value) {
        if (true === \is_array($value)) {
            $newPrefix = ($prefix) ? $prefix . '[' . $name . ']' : $name;

            if (true === \Limepie\arr\is_file_array($value, false)) {
                $result[$newPrefix] = $value;
            } else {
                $result += \Limepie\arr\file_array_flatten($value, $newPrefix);
            }
        }
    }

    return $result;
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

function nest(array $flat, $value = []) : array
{
    if (!$flat) {
        return $value;
    }
    $key = $flat[\key($flat)];
    \array_splice($flat, 0, 1);

    return [$key => \Limepie\arr\nest($flat, $value)];
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
                $tmp_array = \Limepie\arr\flatten($v, $base . $k . $divider_char, $divider_char);
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
    $old = $arraya = \Limepie\arr\flatten($arraya);
    $new = $arrayb = \Limepie\arr\flatten($arrayb);

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

function xml2array(\SimpleXMLElement $xml) : array
{
    $json = \json_encode($xml);

    return \json_decode($json, true);
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
    $paths = \explode('.', $path);

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
                        $newData = \Limepie\arr\refparse($data, $basepath);

                        $return = \array_merge($return, $newData);
                    }
                } else {
                    throw new Exception($orgPath . ' ref error');
                }
            }
        } elseif ('$after' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\arr\insert_after($return, $k, $v1);
                }
            }
        } elseif ('$before' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\arr\insert_before($return, $k, $v1);
                }
            }
        } elseif ('$change' === $key) {
            foreach ($value as $k => $v) {
                if (true === isset($return[$k])) {
                    $return[$k] = \Limepie\arr\merge_deep($return[$k], $v);
                } else {
                    throw new Exception($key . ': Undefined array key "' . $k . '"');
                }
            }
        } elseif ('$merge' === $key) {
            foreach ($value as $k => $v) {
                if (true === isset($return[$k])) {
                    $return[$k] = \Limepie\arr\merge_deep($return[$k], $v);
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
                            $return[$k] = \Limepie\arr\remove($return[$k], $v);
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
                        $return[$key] = \Limepie\arr\refparse($value, $basepath);
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

                    $languageModels = Di::getLanguageModels(null);

                    // 1개면 하나의 언어만 service lang에 세팅되어있다.
                    if ($languageModels?->toCount() > 1) {
                        $desiredOrder = [];

                        foreach ($languageModels as $languageModel) {
                            $desiredOrder[]                          = $languageModel->getId();
                            $langProperties[$languageModel->getId()] = [
                                'label'   => \Limepie\__('core', $languageModel->getName()),
                                'prepend' => '<i class="fi fi-' . $languageModel->getLocaleId() . '"></i>',
                            ] + $default2;
                        }
                    } else {
                        $langProperties = [
                            'ko' => ['label' => \Limepie\__('core', '한국어'), 'prepend' => '<i class="fi fi-kr"></i>'] + $default2,
                            'en' => ['label' => \Limepie\__('core', '영어'), 'prepend' => '<i class="fi fi-us"></i>']  + $default2,
                            'ja' => ['label' => \Limepie\__('core', '일본어'), 'prepend' => '<i class="fi fi-jp"></i>'] + $default2,
                            'zh' => ['label' => \Limepie\__('core', '중국어'), 'prepend' => '<i class="fi fi-cn"></i>'] + $default2,
                        ];
                        // 원하는 순서 배열을 정의합니다.

                        $desiredOrder = ['ko', 'ja', 'en', 'zh'];
                    }
                    // \prx($langProperties);

                    if (isset($value['langs']) && $value['langs']) {
                        $newLangProperties = [];

                        if (\Limepie\arr\is_assoc($value['langs'])) {
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
                                        $newLangProperties[$langKey] = \Limepie\arr\merge_deep($langProperties[$langKey], $langProperty);
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

                    $langClass = '';

                    if ($value['class'] ?? '') {
                        $langClass .= ' ' . $value['class'];
                    }

                    $langGroupClass = '';

                    if ($value['lang_group_class'] ?? '') {
                        $langGroupClass .= ' ' . $value['lang_group_class'];
                    }
                    $value = [
                        'label'       => ($label ?? '') . ' - ' . \Limepie\__('core', '언어팩'),
                        'type'        => 'group',
                        'class'       => \trim($langClass),
                        'group_class' => \trim($langGroupClass),
                        'properties'  => $langProperties,
                    ];
                    $return[$key . '_langs'] = \Limepie\arr\refparse($value, $basepath);
                } else {
                    $return[$key] = \Limepie\arr\refparse($value, $basepath);
                }
            } else {
                $return[$key] = $value;
            }
        }
    }

    // pr($return);

    return $return;
}

function extract_property($array, $propertyName = 'name')
{
    $names = [];

    foreach ($array ?? [] as $row) {
        $names[] = $row[$propertyName];
    }

    return \implode(',', $names);
}
