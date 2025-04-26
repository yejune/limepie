<?php

declare(strict_types=1);

namespace Limepie\arr;

use Limepie\ArrayObject;
use Limepie\Di;
use Limepie\Exception;
use Limepie\Form\Parser;

/**
 * 점(.)으로 구분된 키를 가진 배열을 중첩 배열 구조로 변환합니다.
 *
 * @param array $flatArray 점(.)으로 구분된 키를 가진 1차원 배열
 *                         예: ['a.b.c' => 'value']
 *
 * @return array 중첩된 다차원 배열
 *               예: ['a' => ['b' => ['c' => 'value']]]
 */
function dot_to_nested(array $flatArray) : array
{
    $result = [];

    foreach ($flatArray as $key => $value) {
        $keys    = \explode('.', $key);
        $current = &$result;

        foreach ($keys as $i => $segment) {
            if ($i === \count($keys) - 1) {
                // 마지막 키에 값 할당
                $current[$segment] = $value;
            } else {
                // 중첩 배열 구조 생성
                if (!isset($current[$segment]) || !\is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }

        // 참조 해제
        unset($current);
    }

    return $result;
}
/**
 * 특정 키를 가진 배열 요소만 필터링하는 함수.
 *
 * @param array  $array 필터링할 입력 배열
 * @param string $key   필터링 기준이 되는 키 이름 (기본값: 'images')
 *
 * @return array 지정된 키를 가진 요소들만 포함한 필터링된 배열
 */
function filter_has_key(null|array|ArrayObject $array, string $key = 'images') : array
{
    if (null === $array) {
        return [];
    }

    $result = [];

    foreach ($array as $index => $element) {
        if (isset($element[$key])) {
            $result[$index] = $element;
        }
    }

    return $result;
}

/**
 * 배열을 나열 순서에 따라 여러 그룹으로 분배하는 함수.
 *
 * 이 함수는 배열 요소의 키 값이 아닌 나열된 순서에 따라 여러 그룹으로 나눕니다.
 * 예를 들어, 그룹 수가 2인 경우:
 * - 첫 번째, 세 번째, 다섯 번째... 위치의 요소는 첫 번째 그룹으로
 * - 두 번째, 네 번째, 여섯 번째... 위치의 요소는 두 번째 그룹으로 분배됩니다.
 * 원본 배열의 키는 유지되며, 그룹 수는 매개변수로 조정할 수 있습니다.
 *
 * @param array $array  분리할 배열
 * @param int   $groups 나눌 그룹 수 (기본값: 2)
 *
 * @return array 분배된 그룹의 배열 (원본 키 유지)
 *
 * @throws InvalidArgumentException 그룹 수가 1 미만인 경우
 */
function split(array $array, int $groups = 2) : array
{
    if ($groups <= 0) {
        throw new \InvalidArgumentException('그룹 수는 양수여야 합니다');
    }

    // 그룹 배열 초기화
    $result = [];

    for ($i = 0; $i < $groups; ++$i) {
        $result[$i] = [];
    }

    // 배열 요소를 순서대로 그룹에 분배
    $index = 0;

    foreach ($array as $key => $value) {
        $groupIndex                = $index % $groups; // 순환하며 그룹 인덱스 계산
        $result[$groupIndex][$key] = $value; // 원래 키를 유지
        ++$index;
    }

    return $result;
}

function getIsDisplay($serviceModuleBannerItem)
{
    $isDisplay = false;
    $now       = \date('Y-m-d H:i:s');

    if (1 == $serviceModuleBannerItem['is_display']) {
        $isDisplay = true;
    } elseif (2 == $serviceModuleBannerItem['is_display'] || 3 == $serviceModuleBannerItem['is_display']) {
        $isDateValid = false;

        // 날짜 범위 체크
        if (2 == $serviceModuleBannerItem['is_display']) {
            if ($serviceModuleBannerItem['display_start_dt'] <= $now
            && $serviceModuleBannerItem['display_end_dt'] >= $now) {
                $isDateValid = true;
            }
        } else { // IsDisplay == 3
            if ($serviceModuleBannerItem['display_start_dt'] <= $now) {
                $isDateValid = true;
            }
        }

        if ($isDateValid) {
            if (0 == $serviceModuleBannerItem['is_allday']) {
                $isDisplay = true;
            } else {
                // 현재 요일 가져오기 (0: 일요일, 1: 월요일, ..., 6: 토요일)
                $currentDayOfWeek = \date('w', \strtotime($now));

                // 요일별 체크
                switch ($currentDayOfWeek) {
                    case 0: // 일요일
                        if (1 == $serviceModuleBannerItem['is_sunday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 1: // 월요일
                        if (1 == $serviceModuleBannerItem['is_monday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 2: // 화요일
                        if (1 == $serviceModuleBannerItem['is_tuesday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 3: // 수요일
                        if (1 == $serviceModuleBannerItem['is_wednesday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 4: // 목요일
                        if (1 == $serviceModuleBannerItem['is_thursday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 5: // 금요일
                        if (1 == $serviceModuleBannerItem['is_friday']) {
                            $isDisplay = true;
                        }

                        break;
                    case 6: // 토요일
                        if (1 == $serviceModuleBannerItem['is_saturday']) {
                            $isDisplay = true;
                        }

                        break;
                }
            }
        }
    }

    return $isDisplay;
}

function getDisplayStatus($serviceModuleBannerItem)
{
    $now    = \date('Y-m-d H:i:s');
    $status = [
        'isDisplay'    => false,
        'isAllDisplay' => false,
        'isExpired'    => false,    // 기간이 지나서 안보이는 경우
        'isWaiting'    => false,    // 시작 대기 중인 경우
        'reason'       => '',
    ];

    // is_display가 1인 경우 (항상 표시)
    if (1 == $serviceModuleBannerItem['is_display']) {
        $status['isDisplay']    = true;
        $status['isAllDisplay'] = true;

        return $status;
    }

    // is_display가 2 또는 3인 경우
    if (2 == $serviceModuleBannerItem['is_display'] || 3 == $serviceModuleBannerItem['is_display']) {
        // 시작일이 미래인 경우
        if ($serviceModuleBannerItem['display_start_dt'] > $now) {
            $status['isWaiting'] = true;
            $status['reason']    = '예정';

            return $status;
        }

        // is_display가 2이고 종료일이 과거인 경우
        if (2 == $serviceModuleBannerItem['is_display'] && $serviceModuleBannerItem['display_end_dt'] < $now) {
            $status['isExpired'] = true;
            $status['reason']    = '지남';

            return $status;
        }

        // 날짜가 유효한 경우
        $isDateValid = false;

        if (2 == $serviceModuleBannerItem['is_display']) {
            if ($serviceModuleBannerItem['display_start_dt'] <= $now
                && $serviceModuleBannerItem['display_end_dt'] >= $now) {
                $isDateValid = true;
            }
        } else { // is_display == 3
            if ($serviceModuleBannerItem['display_start_dt'] <= $now) {
                $isDateValid = true;
            }
        }

        if ($isDateValid) {
            // 요일 제한이 없는 경우
            if (0 == $serviceModuleBannerItem['is_allday']) {
                $status['isDisplay'] = true;

                return $status;
            }

            // 요일 체크
            $currentDayOfWeek = \date('w', \strtotime($now));
            $dayMap           = [
                0 => 'is_sunday',
                1 => 'is_monday',
                2 => 'is_tuesday',
                3 => 'is_wednesday',
                4 => 'is_thursday',
                5 => 'is_friday',
                6 => 'is_saturday',
            ];

            if (isset($dayMap[$currentDayOfWeek])
                && 1 == $serviceModuleBannerItem[$dayMap[$currentDayOfWeek]]) {
                $status['isDisplay'] = true;
            } else {
                $status['reason'] = '요일제한';
            }
        }
    }

    return $status;
}

/**
 * 다차원 배열에서 재귀적으로 null 값과 빈 문자열을 제거합니다.
 * 중첩된 배열이 필터링 후 비어있으면 해당 배열도 제거합니다.
 * 0, false, '0' 같은 값은 유지합니다.
 *
 * @param array $array 필터링할 다차원 배열
 *
 * @return array 필터링된 배열
 */
function filter_recursive($array)
{
    foreach ($array as $key => $value) {
        if (\is_array($value)) {
            $array[$key] = filter_recursive($value);

            if (empty($array[$key])) {
                unset($array[$key]);
            }
        } elseif (null === $value || '' === $value) {
            // null과 빈 문자열만 제거하고 false는 유지
            unset($array[$key]);
        }
    }

    return $array;
}

/**
 * 배열의 무작위 위치에 새 항목을 삽입합니다.
 *
 * @param array    $items   새 항목을 삽입할 원본 배열
 * @param mixed    $newItem 삽입할 새 항목
 * @param null|int $max     삽입 가능한 최대 위치(옵션, 기본값: 배열 길이)
 *
 * @return array 항목이 삽입된 새 배열
 *
 * @example
 * // [1, 2, 3, 6, 4, 5] (예시 결과 - 무작위 위치에 삽입됨)
 * $result = insert_random([1, 2, 3, 4, 5], 6);
 *
 * // [1, 6, 2, 3, 4, 5] (최대 위치 1로 제한)
 * $result = insert_random([1, 2, 3, 4, 5], 6, 1);
 */
function insert_random($items, $newItem, $max = null)
{
    // $items = [1, 2, 3, 4, 5];
    // $newItem = 6
    // 1 2 3 6 4 5

    $count = \count($items);

    // $position = \rand(0, \Limepie\get_max_multiple($count, $max + 1));
    $position = \rand(0, $max ? $max : $count);

    \array_splice($items, $position, 0, [$newItem]);

    return $items;
}

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

function key_value($array, $separator = ': ', $delimiter = ', ')
{
    $result = [];

    foreach ($array as $key => $value) {
        $result[] = "{$key}{$separator}{$value}";
    }

    return \implode($delimiter, $result);
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

function replace($description, $row = [])
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
function to_html(array $in, array $hidden_keys = ['password', 're_password']) : string
{
    if (0 < \count($in)) {
        $t = '<div class="table-responsive"><table class="table table-sm table-bordered table-gray-bordered"><tbody>';

        foreach ($in as $key => $value) {
            if (true === is_assoc($in)) {
                // 특정 키를 가지면 value를 히든 처리
                $display_value = \in_array($key, $hidden_keys, true) ? '******' : $value;

                if (true === \is_array($value)) {
                    // 배열인 경우 재귀적으로 to_html 호출 (히든 키도 전달)
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . \Limepie\arr\to_html($value, $hidden_keys) . '</td></tr>';
                } else {
                    $t .= '<tr class="bg-soft-primary"><th>' . $key . '</th><td>' . $display_value . '</td></tr>';
                }
            } else {
                if (true === \is_array($value)) {
                    $t .= '<tr class="bg-soft-primary"><td>' . \Limepie\arr\to_html($value, $hidden_keys) . '</td></tr>';
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

function array_insert_before(&$array, $beforeKey, $newKey, $newValue)
{
    if (\array_key_exists($beforeKey, $array)) {
        $new = [];

        foreach ($array as $key => $value) {
            if ($key === $beforeKey) {
                $new[$newKey] = $newValue;
            }
            $new[$key] = $value;
        }
        $array = $new;

        return true;
    }

    return false;
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

/**
 * 주어진 키 순서대로 배열을 정렬합니다.
 *
 * @param array $array 정렬할 배열
 * @param array $keys  정렬 순서를 정의하는 키 배열
 *
 * @return array 정렬된 배열
 */
function ksort($array, $keys)
{
    // 결과를 저장할 배열
    $result = [];

    // 주어진 키 순서대로 배열 재구성
    foreach ($keys as $key) {
        $result[$key] = $array[$key] ?? [];
    }

    return $result;
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

/**
 * 폼 구성 처리를 위한 메인 함수.
 *
 * @param array  $arr      처리할 폼 구성 배열
 * @param string $basepath 기본 경로
 *
 * @return array 처리된 폼 구성 배열
 */
function refparse($arr = [], $basepath = '') : array
{
    $formProcessor = new Parser($arr, $basepath);

    return $formProcessor->processForm();
}

function legacy_refparse($arr = [], $basepath = '') : array
{
    $return = [];

    // 동적 폼 생성 시 조건부로 요소를 표시하거나 숨기는 기능.
    // 요구사항 정리
    //     조건부 표시 기능:
    //         is_extra 필드 값에 따라 다른 필드들(short_description, cover)의 표시 여부를 제어합니다.
    //         값이 0일 때는 모든 필드 숨김
    //         값이 1일 때는 short_description 표시
    //         값이 2일 때는 cover 표시
    //         값이 3일 때는 short_description과 cover 모두 표시
    //         동적 UI 구현:
    //         선택 변경 시 즉시 UI 업데이트
    //         페이지 로드 시 초기 상태 설정
    //         폼 유효성 검사와 연동
    //     CSS 클래스 생성:
    //         각 요소에 고유한 클래스 할당
    //         요소 선택 및 스타일 적용을 위한 셀렉터 생성
    foreach ($arr ?? [] as $key => $fields) {
        if ($fields['display_switch'] ?? false) {
            $tmpClass = \Limepie\genRandomString();

            // 모든 요소 클래스 문자열 생성
            $allElementsClasses = [];

            // 모든 요소 추적 - 각 요소가 어떤 scriptKey에 속하는지 기록
            $elementToScriptKeys = [];

            foreach ($fields['display_switch'] as $scriptKey => $elements) {
                if (!\is_array($elements)) {
                    continue; // 배열이 아닌 경우 건너뜀
                }

                foreach ($elements as $element) {
                    $element = \trim($element);

                    if (empty($element)) {
                        continue;
                    }

                    $allElementsClasses[] = '.' . \str_replace('[]', '__', $element) . "_{$tmpClass}";

                    // 이 요소가 속한 scriptKey 기록
                    if (!isset($elementToScriptKeys[$element])) {
                        $elementToScriptKeys[$element] = [];
                    }
                    $elementToScriptKeys[$element][] = $scriptKey;
                }
            }

            // 중복 제거
            $allElementsClasses  = \array_unique($allElementsClasses);
            $allElementsSelector = \implode(', ', $allElementsClasses);

            // 각 스크립트 키에 대한 조건문 생성
            $jsConditions = [];

            foreach ($fields['display_switch'] as $scriptKey => $elements) {
                if (!\is_array($elements)) {
                    continue; // 배열이 아닌 경우 건너뜀
                }

                $showElements = [];

                foreach ($elements as $element) {
                    $element = \trim($element);

                    if (empty($element)) {
                        continue;
                    }

                    $showElements[] = "\$self.closest('.form-group').find('." . \str_replace('[]', '__', $element) . "_{$tmpClass}').show();";
                }

                $jsConditions[] = "if(this.value == '{$scriptKey}') { " . \implode(' ', $showElements) . ' }';
            }

            // onchange 핸들러 추가 - 표시/숨김 처리 부분
            if (!empty($jsConditions)) {
                $arr[$key]['onchange'] = <<<SQL
                    var \$self = $(this);
                    // 모든 요소 숨기기
                    \$self.closest('.form-group').find('{$allElementsSelector}').hide();

                    // 현재 값에 따라 해당 요소만 표시
                    {$jsConditions[0]}
                SQL;

                // 나머지 조건 추가
                for ($i = 1; $i < \count($jsConditions); ++$i) {
                    $arr[$key]['onchange'] .= " else {$jsConditions[$i]}";
                }

                // 기본 케이스 추가 - 일치하는 조건이 없을 때 모든 요소 숨김
                $arr[$key]['onchange'] .= " else { \$self.closest('.form-group').find('{$allElementsSelector}').hide(); }";

                // 폼 유효성 검사 코드 추가
                $validElements = [];

                foreach ($fields['display_switch'] as $scriptKey => $elements) {
                    if (!\is_array($elements)) {
                        continue;
                    }

                    foreach ($elements as $element) {
                        $element = \trim($element);

                        if (empty($element)) {
                            continue;
                        }

                        $validElements[] = '.' . \str_replace('[]', '__', $element) . "_{$tmpClass}";
                    }
                }

                // 중복 제거 및 문자열 연결
                $validElements    = \array_unique($validElements);
                $validElementsStr = \implode(', ', $validElements);

                // 유효성 검사 코드 추가
                $arr[$key]['onchange'] .= <<<SQL
                    if(\$self.closest('form').length > 0) {
                        var elementsToCheck = \$('.valid-target', \$self.closest('.form-group').find('{$validElementsStr}').closest('.form-element-wrapper'));
                        if(elementsToCheck.length > 0) {
                            \$self.closest('form').validate().checkByElements(elementsToCheck);
                        }
                    }
                SQL;
            }

            $diffKeys = [];

            // items에는 있으나 display_switch에는 없는 키가 있는 경우 숨김 처리를 하기 위한 키 찾기
            if (true === isset($fields['items']) && true === isset($fields['display_switch'])) {
                $itemKeys   = \array_keys($fields['items']);
                $scriptKeys = \array_keys($fields['display_switch']);
                $diffKeys   = \array_diff($itemKeys, $scriptKeys);
            }

            foreach ($fields['display_switch'] as $scriptKey => $elements) {
                if (!\is_array($elements)) {
                    continue;
                }

                foreach ($elements as $element) {
                    $element = \trim($element);

                    if (empty($element)) {
                        continue;
                    }

                    // class 업데이트
                    $arr[$element]['class'] = ($arr[$element]['class'] ?? '') . ' ' . \str_replace('[]', '__', $element) . '_' . $tmpClass;

                    // display 조건 설정
                    $arr[$element]['display_target']                 = '.' . $key;
                    $arr[$element]['display_target_condition_class'] = [];

                    // 기본적으로 모든 값에 대해 숨김 처리
                    if (!isset($arr[$element]['display_target_condition_style'])) {
                        $arr[$element]['display_target_condition_style'] = [];

                        // items에는 있으나 display_switch에는 없는 키가 있는 경우 숨김 처리
                        foreach ($diffKeys as $diffKey) {
                            $arr[$element]['display_target_condition_style'][$diffKey] = 'display: none;';
                        }
                    }

                    // 중요: 처음 로드 시 모든 요소를 기본으로 숨김 처리
                    $arr[$element]['style'] = ($arr[$element]['style'] ?? '') . '; display: none;';

                    // 현재 scriptKey에 대해서는 표시
                    $arr[$element]['display_target_condition_style'][$scriptKey] = 'display: block';

                    // 다른 scriptKey들에 대해서는 숨김
                    foreach ($fields['display_switch'] as $otherKey => $otherValue) {
                        if ($otherKey !== $scriptKey) {
                            $arr[$element]['display_target_condition_style'][$otherKey] = 'display: none';
                        }
                    }

                    // // 코드의 핵심 수정 부분: 정의되지 않은 키에 대한 처리
                    // // 모든 가능한 scriptKey 값에 대해 명시적으로 지정
                    // // 예: 0, 1, 2, 3, ... 등 없는 키에 대해서도 숨김 처리
                    // $arr[$element]['display_target_condition_default_style'] = 'display: none;';

                    // // 현재 선택된 값이 없는 경우 기본적으로 숨김
                    // $arr[$element]['display_target_condition_default'] = 'true';
                }
            }

            // 페이지 로드 시 초기 상태 설정을 위한 JavaScript 추가
            // 이 부분은 페이지 로드 후 한 번 수행되며, 초기 선택값에 따라 요소를 표시하거나 숨김
            if (!empty($allElementsClasses)) {
                if (!isset($arr[$key]['ready'])) {
                    $arr[$key]['ready'] = '';
                }

                $arr[$key]['ready'] .= <<<SQL
                    // 페이지 로드 시 초기 상태 설정
                    var initVal = $(this).val();
                    var \$self = $(this);
                    // 기본적으로 모든 요소 숨기기
                    \$self.closest('.form-group').find('{$allElementsSelector}').hide();

                    // 초기 선택값에 따라 요소 표시
        SQL;

                foreach ($fields['display_switch'] as $scriptKey => $elements) {
                    if (!\is_array($elements) || empty($elements)) {
                        continue;
                    }

                    $showElementsInit = [];

                    foreach ($elements as $element) {
                        $element = \trim($element);

                        if (empty($element)) {
                            continue;
                        }
                        $showElementsInit[] = "\$self.closest('.form-group').find('." . \str_replace('[]', '__', $element) . "_{$tmpClass}').show();";
                    }

                    if (!empty($showElementsInit)) {
                        $arr[$key]['ready'] .= <<<SQL

                        if(initVal == '{$scriptKey}') {
                            {$showElementsInit[0]}
        SQL;

                        for ($i = 1; $i < \count($showElementsInit); ++$i) {
                            $arr[$key]['ready'] .= " {$showElementsInit[$i]}";
                        }
                        $arr[$key]['ready'] .= ' }';
                    }
                }
            }
        }
    }

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
                        $newData = \Limepie\arr\legacy_refparse($data, $basepath);

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
                if (true === isset($value['lang']) && 1 === \preg_match('#\[\]$#', $key, $m)) {
                    if ('group' === $value['type']) {
                        // form 이 바뀌어야만 성립한다. 그러므로 지원하지 않음을 밝히고 form 자체를 수정하게 권고한다.
                        throw new Exception('[] multiple은 lang옵션을 지원하지 않습니다. group 하위로 옮기세요.');
                    }

                    if (false === isset($value['lang_key'])) {
                        throw new Exception('multiple type에서 lang 지정시 하위에서 사용할 lang_key 가 필요합니다.');
                    }

                    $langKey                                 = $value['lang_key'];
                    $default                                 = $value;
                    $orginType                               = $default['type'];
                    $default['type']                         = 'group';
                    $default['properties'][$langKey]         = $default;
                    $default['properties'][$langKey]['type'] = $orginType;

                    if ('append' === $default['lang'] && !isset($default['langs'])) {
                        unset($default['properties'][$langKey]['rules']['required']);
                    }

                    unset(
                        $default['lang'],
                        $default['lang_key'],
                        $default['rules'],
                        $default['label'],
                        $default['properties'][$langKey]['multiple'],
                        $default['properties'][$langKey]['sortable'],
                        $default['properties'][$langKey]['display_target'],
                        $default['properties'][$langKey]['display_target_condition'],
                        $default['properties'][$langKey]['display_target_condition_class'],
                        $default['properties'][$langKey]['display_target_condition_style'],
                    );

                    $default['properties'][$langKey] = \Limepie\arr\legacy_refparse($default['properties'][$langKey], $basepath);

                    $return[$key] = \Limepie\arr\legacy_refparse($default, $basepath);
                    // \prx($key, $return[$key]);

                    // exit;
                    // if (isset($value['properties']['langs']['lang'])) {
                    //     unset($value['properties']['langs']['lang']);
                    // }
                } elseif (true === isset($value['lang'])) {
                    $isRemoveLabel = ($value['remove_lang_title'] ?? false);
                    $isLangAppend  = 'append' === $value['lang'];
                    $orgClass      = $value['class'] ?? '';

                    if ('append' === $value['lang']) {
                        if (true === $isRemoveLabel) {
                            $value['class'] = $orgClass . ' pb-1';
                        }
                        $return[$key] = \Limepie\arr\legacy_refparse($value, $basepath);
                    }

                    $value['class'] = $orgClass;

                    $default = $value;
                    unset(
                        $default['lang'],
                        $default['class'],
                        $default['style'],
                        $default['description'],
                        $default['default'],
                        $default['display_target'],
                        $default['display_target_condition'],
                        $default['display_target_condition_class'],
                        $default['display_target_condition_style'],
                    );
                    $appendLangProperties = $default;

                    // append이고 langs으로 lang을 특정하지 않았을 경우
                    // rules required를 false 처리해준다.
                    // 원하지 않는 언어가 강제 required되는 상황 방지.
                    // langs를 입력했다는건 사용하겠다는 의미로 부모의 properties를 그대로 사용한다.
                    if ('append' === $value['lang'] && !isset($value['langs'])) {
                        unset(
                            $appendLangProperties['rules']['required'],
                        );
                        // \prx($arr);
                    }

                    // unset($appendLangProperties['label']);
                    if ($value['label'] ?? false) {
                        if (true === \is_array($value['label'])) {
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
                            $desiredOrder[] = $languageModel->getId();
                            $properties     = ['prepend' => '<i class="fi fi-' . $languageModel->getLocaleId() . '" title="' . $languageModel->getName() . '"></i>'];

                            if (true === $isRemoveLabel) {
                                unset($properties['label'] , $appendLangProperties['label']);
                                $properties['class'] = ($properties['class'] ?? '') . ' border-0 pb-1 pt-0';
                            } else {
                                $properties['label'] = \Limepie\__('core', $languageModel->getName());
                            }

                            $langProperties[$languageModel->getId()] = $properties + $appendLangProperties;
                        }
                    } else {
                        $langProperties = [];
                        $desiredOrder   = ['ko', 'ja', 'en', 'zh'];

                        $languages = [
                            'ko' => ['name' => '한국어', 'locale' => 'kr'],
                            'en' => ['name' => '영어', 'locale' => 'us'],
                            'ja' => ['name' => '일본어', 'locale' => 'jp'],
                            'zh' => ['name' => '중국어', 'locale' => 'cn'],
                        ];

                        foreach ($languages as $code => $lang) {
                            $properties = ['prepend' => '<i class="fi fi-' . $lang['locale'] . '" title="' . $lang['name'] . '"></i>'];

                            if (true === $isRemoveLabel) {
                                unset($properties['label'] ,$appendLangProperties['label']);
                                $properties['class'] = ($properties['class'] ?? '') . ' border-0 pb-1 pt-0';
                            } else {
                                $properties['label'] = \Limepie\__('core', $lang['name']);
                            }

                            $langProperties[$code] = $properties + $appendLangProperties;
                        }
                    }

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

                    $langClass = '';

                    if ($value['class'] ?? '') {
                        $langClass .= ' ' . $value['class'];
                    }

                    $langGroupClass = '';

                    if ($value['lang_group_class'] ?? '') {
                        $langGroupClass .= ' ' . $value['lang_group_class'];
                    }

                    $languagePackName = \Limepie\__('core', '언어팩');

                    if ($value['lang_name'] ?? false) {
                        $languagePackName = $value['lang_name'];
                    }

                    $display_target                 = $value['display_target']                 ?? '';
                    $display_target_condition       = $value['display_target_condition']       ?? [];
                    $display_target_condition_class = $value['display_target_condition_class'] ?? [];
                    $display_target_condition_style = $value['display_target_condition_style'] ?? [];
                    $value                          = [
                        'label'       => ($label ?? '') . ' - ' . $languagePackName,
                        'type'        => 'group',
                        'class'       => \trim($langClass) . (true === $isRemoveLabel ? ' border-0 pt-0' : ''),
                        'group_class' => \trim($langGroupClass) . (true === $isRemoveLabel ? ' p-1' : ''),
                        'properties'  => $langProperties,
                    ];

                    if ($isLangAppend && true === $isRemoveLabel) {
                        unset($value['label']);
                    }

                    if ($display_target) {
                        $value['display_target'] = $display_target;
                    }

                    if ($display_target_condition) {
                        $value['display_target_condition'] = $display_target_condition;
                    }

                    if ($display_target_condition_class) {
                        $value['display_target_condition_class'] = $display_target_condition_class;
                    }

                    if ($display_target_condition_style) {
                        $value['display_target_condition_style'] = $display_target_condition_style;
                    }

                    $return[$key . '_langs'] = \Limepie\arr\legacy_refparse($value, $basepath);
                } else {
                    $return[$key] = \Limepie\arr\legacy_refparse($value, $basepath);
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
