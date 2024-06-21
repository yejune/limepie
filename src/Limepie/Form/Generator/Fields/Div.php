<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Div extends Fields
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
        // $default = $property['default'] ?? '';

        $elementClass = ' ';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = $property['element_class'];
        }

        return <<<EOT
            <div class="{$elementClass}"></div>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;

        return <<<EOT
            {$value}
        EOT;
    }
}
