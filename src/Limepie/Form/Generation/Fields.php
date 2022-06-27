<?php declare(strict_types=1);

namespace Limepie\Form\Generation;

class Fields
{
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

        return $dotName;
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
        if (true === \Limepie\is_file_array($value, true)) {
            return true;
        }

        if ($value instanceof \Limepie\ArrayObject) {
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
        //return '<i class="button-collapse glyphicon glyphicon-triangle-' . $arrow . '"></i> ';
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
        if (true === \Limepie\is_file_array($value, true)) {
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
        //pr($key, $value);
        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return '';
        }

        return $value;
    }

    public static function getValueByDot($data, $key)
    {
        $keys  = \explode('.', $key);
        $value = $data;

        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return null;
        }

        return $value;
    }

    public static function getDefaultByDot($spec, $key)
    {
        $keys  = \explode('.', $key);
        $value = $spec;
        //pr($spec, $keys);
        foreach ($keys as $id) {
            if (1 === \preg_match('#__([^_]{13})__#', $id)) {
                // pr($id);
                // $value = $value[$id];

                continue;
            }
            // pr($id);

            if (true === isset($value['properties'][$id])) {
                $value = $value['properties'][$id];

                continue;
            }

            if (true === isset($value['properties'][$id . '[]'])) {
                $value = $value['properties'][$id . '[]'];

                continue;
            }

            return null;
        }

        return $value['default'] ?? null;
    }

    public static function addElement($html, int $index, bool $isMultiple, bool $isSotableButton, bool $isValue, string $parentId, $multiple_button_onclick = '')
    {
        $btn = '';

        if (true === \is_array($html)) {
            $btn  = $html[1];
            $html = $html[0];
        }

        $class = '';

        if (0 === \strpos(\trim($html), "<div class='fieldset")) {
            $class = ''; //' btn-block';
        }

        if (true === $isMultiple) {
            $click = '';

            if ($multiple_button_onclick) {
                $click = ' onclick="' . $multiple_button_onclick . '"';
            }

            if ($isSotableButton) {
                $btn .= '<span class="btn btn-move fas fa-expand-arrows-alt"></span>';
            }

            $btn .= '<button class="btn btn-plus" type="button"' . $click . '><span class="fas fa-plus"></span></button>';

            if (1 < $index) {
                $btn .= '<button class="btn btn-minus" type="button"' . $click . '><span class="fas fa-minus"></span></button>';
            } else {
                $btn .= '<button class="btn btn-minus" type="button"' . $click . '><span class="fas fa-minus"></span></button>';
            }
        }
        $addClass = '';

        if ($btn) {
            $html .= '<span class="btn-group input-group-btn' . $class . '">' . $btn . '</span>';
        }

        return '<div data-uniqid="' . $parentId . '" class="wrap-element ' . (1 < $index ? 'clone-element' : '') . '' . $addClass . '">' . $html . '</div>';
    }

    public static function readElement($html, int $index = 1)
    {
        return '<div class="wrap-element ' . (1 < $index ? 'clone-element' : '') . '">' . $html . '</div>';
    }
}
