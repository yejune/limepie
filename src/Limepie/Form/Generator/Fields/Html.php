<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Html extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $styleTag     = '';
        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }

        $groupId = 'blank_' . \Limepie\uniqid();
        $elementClass .= ' ' . $groupId;

        if (true === isset($property['empty_style'])) {
            $styleTag .= <<<STYLE
            <style>
            .form-container .{$groupId}:empty {
                {$property['empty_style']}
            }
            </style>
            STYLE;
        }

        if (true === isset($property['empty_message'])) {
            $styleTag .= <<<STYLE
            <style>
            .form-container .{$groupId}:empty::before {
                content: "{$property['empty_message']}";
            }
            </style>
            STYLE;
        }

        return <<<EOT
            {$styleTag}
            <div class="form-control {$elementClass}">{$value}</div>
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
