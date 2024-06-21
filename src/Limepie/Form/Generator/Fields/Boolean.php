<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Boolean extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $value = (string) $value;

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }

        $checked = ((bool) $value) ? 'checked' : '';

        $onclick = '';

        if (true === isset($property['onclick'])) {
            $onclick = 'onclick="' . \Limepie\minify_js($property['onclick']) . '"';
        }
        $text = '';

        if (true === isset($property['text'])) {
            $text = ' ' . $property['text'];
        }

        return <<<EOT
            <label style='font-weight: normal'>
            <input type="checkbox" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="1" {$checked} {$onclick} />{$text}
            </label>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;

        return <<<EOT
        {$value}

EOT;
    }
}
