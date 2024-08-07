<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Dummy extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        // $value = \htmlspecialchars((string) $value);

        // if (0 === \strlen($value) && true === isset($property['default'])) {
        //     $value = \htmlspecialchars((string) $property['default']);
        // }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }

        $elementStyle = '';

        if (isset($property['element_style']) && $property['element_style']) {
            $elementStyle = ' ' . $property['element_style'];
        }

        if (true === isset($property['items']) && true === \is_array($property['items'])) {
            $value = $property['items'][$value] ?? $value;
        }

        if ($value) {
            $value = \nl2br((string) $value);
        }

        return <<<EOT
        <div class="{$elementClass}" style="{$elementStyle}">{$value}</div>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);

        return <<<EOT
            {$value}

        EOT;
    }
}
