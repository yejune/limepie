<?php

namespace Limepie;

/**
 * 배열 조작 유틸리티 클래스.
 */
class ArrayUtil
{
    /**
     * 배열에 항목을 특정 키 다음에 삽입.
     *
     * @param mixed $newItem
     */
    public static function insertAfter(array $array, string $key, $newItem) : array
    {
        $index = \array_search($key, \array_keys($array));

        if (false !== $index) {
            return \array_slice($array, 0, $index + 1, true)
                      + $newItem
                      + \array_slice($array, $index + 1, null, true);
        }

        return $array;
    }

    /**
     * 배열에 항목을 특정 키 이전에 삽입.
     *
     * @param mixed $newItem
     */
    public static function insertBefore(array $array, string $key, $newItem) : array
    {
        $index = \array_search($key, \array_keys($array));

        if (false !== $index) {
            return \array_slice($array, 0, $index, true)
                      + $newItem
                      + \array_slice($array, $index, null, true);
        }

        return $array;
    }

    /**
     * 배열 깊은 병합.
     */
    public static function mergeDeep(array $array1, array $array2) : array
    {
        return arr::merge_deep($array1, $array2);
    }

    /**
     * 배열에서 특정 항목 제거.
     */
    public static function remove(array $array, array $removeKeys) : array
    {
        return arr::remove($array, $removeKeys);
    }
}
