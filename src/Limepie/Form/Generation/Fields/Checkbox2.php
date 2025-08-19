<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Checkbox2 extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
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
            $onclick = 'onclick="' . \trim(\addcslashes($property['onclick'], '"')) . '"';
        }

        $style = '';

        if (isset($property['style']) && $property['style']) {
            $style = ' style="' . $property['style'] . '"';
        }

        $html = <<<EOT
        <div><input type="checkbox" class="xform-control" name="{$key}" data-rule-name="{$ruleName}" value="1" {$checked} {$onclick}{$style} /></div>

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
