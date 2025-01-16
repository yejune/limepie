<?php

declare(strict_types=1);

namespace Limepie\Form\Generator;

use Limepie\ArrayObject;
use Limepie\Exception;

class Fields
{
    public static $ruleNameConcat = false;

    public static $allData = [];

    public static $conditions = [];

    public static $specs = [];

    public static $reverseConditions = [];

    public static function getNameByDot($dotName)
    {
        $parts = \explode('.', $dotName);

        if (1 < \count($parts)) {
            $first = \array_shift($parts);

            return $first . '[' . \implode('][', $parts) . ']';
        }

        return $dotName;
    }

    public static function getNameByArray($parts)
    {
        if (1 < \count($parts)) {
            $first = \array_shift($parts);

            return $first . '[' . \implode('][', $parts) . ']';
        }

        // return $dotName;
    }

    // public static function getMultipleHtml($key)
    // {
    //     return '<span class="btn-group input-group-btn wrap-btn-plus" data-uniqid="'.$key.'"><button class="btn btn-plus" type="button">2222<span class="fas fa-plus"></span></button></span>';
    // }

    public static function getKey(string $key, string $id) : string
    {
        return \str_replace('[]', '[' . $id . ']', $key);
        // return \preg_replace_callback('#\[\]#', function($match) {
        //     return '[' . static::getUniqueId() . ']';
        // }, $key);
    }

    public static function getUniqueId()
    {
        return '__' . \uniqid() . '__';
    }

    // arr[arr[]] 형태를 arr[arr][]로 교정
    public static function fixKey(string $key) : string
    {
        $arrCount = \substr_count($key, '[]');

        return '[' . \str_replace('[]', '', $key) . ']' . \str_repeat('[]', $arrCount);
    }

    public static function fixKey2(string $key) : string
    {
        return '[' . \str_replace('[]', '', $key) . ']';
    }

    public static function isValue($value)
    {
        if (true === \Limepie\arr\is_file_array($value, true)) {
            return true;
        }

        if ($value instanceof ArrayObject) {
            $value = $value->toArray();
        }

        if (true === \is_object($value)) {
            if (true === \property_exists($value, 'value')) {
                $value = $value->value;
            }
        }

        if (true === \is_array($value)) {
            if (0 < \count($value)) {
                return true;
            }

            return false;
        }

        if (\strlen((string) $value)) {
            return true;
        }

        return false;
    }

    public static function arrow($is)
    {
        if (true === $is) {
            $arrow = 'bottom';
        } else {
            $arrow = 'right';
        }

        return '<span class="button-collapse" data-feather="chevron-' . $arrow . '"></span>';
        // return '<i class="button-collapse glyphicon glyphicon-triangle-' . $arrow . '"></i> ';
    }

    public static function testValue($value)
    {
        if (\is_array($value)) {
            $r = true;
            $c = \count($value);
            $j = 0;

            foreach ($value as $v) {
                $a = static::testValue($v);

                if (false === $a) {
                    ++$j;
                }
            }

            if ($c === $j) {
                return false;
            }

            return true;
        }

        if (\strlen((string) $value)) {
            return true;
        }

        return false;
    }

    public static function isValue2($value)
    {
        if (true === \Limepie\arr\is_file_array($value, true)) {
            return true;
        }

        if (true === \is_array($value)) {
            return static::testValue($value);
        }

        if (\strlen((string) $value)) {
            return true;
        }

        return false;
    }

    public static function getLanguage()
    {
        return \Limepie\get_language();
    }

    public static function getValueByArray($data, $key)
    {
        $keys  = \explode('[', \str_replace([']'], '', \str_replace('[]', '', $key)));
        $value = $data;

        // pr($key, $value);
        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return '';
        }

        return $value;
    }

    public static function getFixedPath($map, $path)
    {
        $dotNameParts      = \explode('.', $map);
        $fixedDotNameParts = [];
        $dotNameKeys       = [];

        foreach ($dotNameParts as $part) {
            if (1 === \preg_match('#__([^_]{13})__#', $part)) {
                $dotNameKeys[]       = $part;
                $fixedDotNameParts[] = '*';
            } else {
                $fixedDotNameParts[] = $part;
            }
        }

        $fixedDotName = \implode('.', $fixedDotNameParts);

        $targetFixedDotNameParts = \explode('.', $path);
        $targetFixedDotNameKeys  = [];

        foreach ($targetFixedDotNameParts as $part2) {
            if ('*' === $part2) { // *일 경우 순서대로 dot name keys를 꺼내서 보정
                $dotNameKey = \array_shift($dotNameKeys);

                if (!$dotNameKey) {
                    throw (new Exception('error'))->setDebugMessage('fixed error', __FILE__, __LINE__);
                }
                $targetFixedDotNameKeys[] = $dotNameKey;
            } else {
                $targetFixedDotNameKeys[] = $part2;
            }
        }

        return \implode('.', $targetFixedDotNameKeys);
        // \pr($map, $path, $fixedDotName, $targetFixedDotName);
    }

    public static function getValueByDot($data, $key)
    {
        $keys  = \explode('.', $key);
        $value = $data;

        foreach ($keys as $id) {
            if (true === \is_object($value)) {
                $value = $value->value;
            }

            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return null;
        }

        return $value;
    }

    public static function getValueByDot2($data, $key)
    {
        $keys  = \explode('.', $key);
        $value = $data;

        foreach ($keys as $id) {
            if (true === \is_object($value)) {
                $value = $value->property['properties'];
            }

            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return null;
        }

        return $value;
    }

    public static function getDefaultByDot($spec, $data, $key)
    {
        $keys     = \explode('.', $key);
        $property = $spec;

        // pr($spec, $keys);
        $idx      = '';
        $prevKey  = null;
        $chkArray = null;

        $value = static::getValueByDot2($data, $key);
        // \prx($data, $key);

        foreach ($keys as $id) {
            if (1 === \preg_match('#__([^_]{13})__#', $id) || '*' == $id) {
                $prevKey  = true;
                $chkArray = false;
            } else {
                // 이전 키가 13자리 배열 키가 아니다. 그런데 배열이다.
                if (true === $chkArray && false === $prevKey) {
                    throw (new Exception('error'))->setDebugMessage($key . ' key not found', __FILE__, __LINE__);
                }

                if (true === isset($property['properties'][$id])) {
                    $chkArray = false;
                    $property = $property['properties'][$id];

                    // \prx($property, $value);

                    // []가 아닌데 multiple이 있다.
                    if (true === isset($property['multiple']) && $property['multiple']) {
                        if (false === isset($property['properties'])) {
                            // custom multiple일 가능성
                            $property = $value;

                            return $property['default'] ?? null;
                            // \prx($property);
                        }

                        throw (new Exception('multiple spec error'))->setDebugMessage('multiple spec error', __FILE__, __LINE__);
                    }
                } elseif (true === isset($property['properties'][$id . '[]'])) {
                    $chkArray = true;
                    $property = $property['properties'][$id . '[]'];

                    // []인데 multiple이 없다.
                    if (false === isset($property['multiple']) || !$property['multiple']) {
                        throw (new Exception('is not multiple ' . $key))->setDebugMessage('is not multiple ' . $key, __FILE__, __LINE__);
                    }
                } else {
                    // $id가 $id or $id[] 둘다 없다.

                    throw (new Exception('#1 not found key ' . $key))->setDebugMessage('not found key ' . $key, __FILE__, __LINE__);
                }

                $prevKey = false;
            }
        }
        // \pr($spec, $property, $keys, $property);

        return $property['default'] ?? null;
    }

    public static function getSpecByDot($spec, $key)
    {
        $keys     = \explode('.', $key);
        $property = $spec;
        // pr($spec, $keys);
        $idx      = '';
        $prevKey  = null;
        $chkArray = null;

        foreach ($keys as $id) {
            if (1 === \preg_match('#__([^_]{13})__#', $id) || '*' == $id) {
                $prevKey  = true;
                $chkArray = false;
            } else {
                // 이전 키가 13자리 배열 키가 아니다. 그런데 배열이다.
                if (true === $chkArray && false === $prevKey) {
                    // \var_dump($chkArray, $prevKey, $key);

                    throw (new Exception('error'))->setDebugMessage($key . ' key not found', __FILE__, __LINE__);
                }

                if (true === isset($property['properties'][$id])) {
                    $chkArray = false;
                    $property = $property['properties'][$id];

                    // []가 아닌데 multiple이 있다.
                    if (true === isset($property['multiple']) && $property['multiple']) {
                        throw (new Exception('multiple spec error'))->setDebugMessage('multiple spec error', __FILE__, __LINE__);
                    }
                } elseif (true === isset($property['properties'][$id . '[]'])) {
                    $chkArray = true;
                    $property = $property['properties'][$id . '[]'];

                    // []인데 multiple이 없다.
                    if (false === isset($property['multiple']) || !$property['multiple']) {
                        throw (new Exception('is not multiple ' . $key))->setDebugMessage('is not multiple ' . $id, __FILE__, __LINE__);
                    }
                } else {
                    // $id가 $id or $id[] 둘다 없다.
                    throw (new Exception('#2 not found key ' . $key))->setDebugMessage('not found key ' . $key, __FILE__, __LINE__);
                }

                $prevKey = false;
            }
        }
        // \pr($spec, $property, $keys, $property);

        return $property ?? [];
    }

    public static function addElement($innerHtml, int $index, bool $isValue, string $parentId, $propertyValue)
    {
        $isMultiple = false;

        if (true === isset($propertyValue['multiple']) && $propertyValue['multiple']) {
            $isMultiple = $propertyValue['multiple'];
        }

        $isSotable = false;

        if (true === isset($propertyValue['sortable']) && $propertyValue['sortable']) {
            $isSotable = $propertyValue['sortable'];
        }

        $wrapperClass = '';

        if (true === isset($propertyValue['wrapper_class']) && $propertyValue['wrapper_class']) {
            $wrapperClass = $propertyValue['wrapper_class'];
        }

        $wrapperStyle = '';

        if (true === isset($propertyValue['wrapper_style']) && $propertyValue['wrapper_style']) {
            $wrapperStyle = $propertyValue['wrapper_style'];
        }

        $btnGroupHtml = '';

        if (true === \is_array($innerHtml)) { // 파일 첨부처럼 element와 button이 같이 존재하는 경우
            $btnGroupHtml = $innerHtml[1];
            $innerHtml    = $innerHtml[0];
        }

        $class = '';

        if (0 === \strpos(\trim($innerHtml), "<div class='form-element")) {
            $class = ''; // ' btn-block';
        }

        if (true === $isMultiple) {
            $dynamicOnchangeScript = '';

            if (true === isset($propertyValue['dynamic_onchange']) && $propertyValue['dynamic_onchange']) {
                $dynamicOnchangeScript = ' onclick="' . \addcslashes($propertyValue['dynamic_onchange'], '"') . '"';
            }

            if ($isSotable) {
                $btnGroupHtml .= '<button  type="button" class="btn btn-move-up"' . $dynamicOnchangeScript . '>&nbsp;</button>';
                $btnGroupHtml .= '<button  type="button" class="btn btn-move-down"' . $dynamicOnchangeScript . '>&nbsp;</button>';
            }

            $plusBtn  = 'btn-plus';
            $minusBtn = 'btn-minus';
            $btnGroupHtml .= '<button class="btn ' . $plusBtn . '" type="button"' . $dynamicOnchangeScript . '>&nbsp;</button>';

            if (true === isset($propertyValue['multiple_copy']) && $propertyValue['multiple_copy']) {
                $plusBtn = 'btn-copy';
                $btnGroupHtml .= '<button class="btn ' . $plusBtn . '" type="button"' . $dynamicOnchangeScript . '>&nbsp;</button>';
                $minusBtn = 'btn-minus btn-delete';
                $plusBtn  = 'btn-plus';
            }

            if (1 < $index) {
                $btnGroupHtml .= '<button class="btn ' . $minusBtn . '" type="button"' . $dynamicOnchangeScript . '>&nbsp;</button>';
            } else {
                $btnGroupHtml .= '<button class="btn ' . $minusBtn . '" type="button"' . $dynamicOnchangeScript . '>&nbsp;</button>';
            }
        }

        if ($btnGroupHtml) {
            $innerHtml .= '<span class="btn-group input-group-btn' . $class . '">' . $btnGroupHtml . '</span>';
        }

        return '<div data-uniqid="' . $parentId . '" class="input-group-wrapper'
            . (1 < $index ? ' clone-element' : '') . ''
            . ($wrapperClass ? ' ' . $wrapperClass : '') . '" style="' . $wrapperStyle . '">' . $innerHtml . '</div>';
    }

    public static function readElement($innerHtml, int $index = 1)
    {
        return '<div class="input-group-wrapper ' . (1 < $index ? 'clone-element' : '') . '">' . $innerHtml . '</div>';
    }
}
