<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Checkbox2 extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $value = (string) $value;

        if (true === isset($property['checked'])) {
            $checked = " checked='checked'";
        } else {
            if (0 === \strlen($value) && true === isset($property['default'])) {
                $value = (string) $property['default'];
            }

            $checked = ((bool) $value) ? 'checked' : '';
        }

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $onclick = '';

        if (true === isset($property['onclick'])) {
            $onclick = 'onclick="' . \Limepie\minify_js($property['onclick']) . '"';
        }

        $style = '';

        if (isset($property['style']) && $property['style']) {
            $style = ' style="' . $property['style'] . '"';
        }

        $html = <<<EOT
            <div><input type="checkbox" class="valid-target" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="1" {$checked} {$onclick}{$style} /></div>

        EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;

        return <<<EOT
        {$value}

EOT;
    }
}
