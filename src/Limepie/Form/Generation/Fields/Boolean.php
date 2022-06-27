<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Boolean extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        $value = (string) $value;

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }

        $checked = ((bool) $value) ? 'checked' : '';

        $onclick = '';

        if (true === isset($property['onclick'])) {
            $onclick = 'onclick="' . \trim(\addcslashes($property['onclick'], '"')) . '"';
        }
        $text = '';

        if (true === isset($property['text'])) {
            $text = ' ' . $property['text'];
        }

        return <<<EOT
        <label style='font-weight: normal'>
        <input type="checkbox" class="form-control" name="{$key}" data-rule-name="{$ruleName}" value="1" {$checked} {$onclick} />{$text}
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
