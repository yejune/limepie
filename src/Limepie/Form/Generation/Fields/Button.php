<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Button extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }
        $onclick = '';
        if (isset($property['onclick']) && $property['onclick']) {
            $onclick = ' onclick="'.$property['onclick'].'"';
        }
        $class = '';
        if (isset($property['element_class']) && $property['element_class']) {
            $class = ' '.$property['element_class'];
        }

        $html = <<<EOT
        <input type="button" class="btn{$class}" name="{$key}" value="{$property['text']}" data-default="{$default}"${readonly}${onclick} />

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
