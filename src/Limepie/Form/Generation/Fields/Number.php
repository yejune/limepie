<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Number extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if (true === \is_array($value)) {
            \pr($key, $value);
        }
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default  = $property['default'] ?? '';
        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $style = '';
        if(true === isset($property['readonly']) && $property['readonly']) {
            if(false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= ' pointer-events: none;';
        }
        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        $onchange = '';

        if (true === isset($property['onchange'])) {
            $onchange = ' onchange="' . \trim(\addcslashes($property['onchange'], '"')) . '"';

            $onchange .= ' onkeyup="' . \trim(\addcslashes($property['onchange'], '"')) . '"';
        }

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
<div class="input-group-prepend">
<span class="input-group-text">{$property['prepend']}</span>
</div>
EOD;
        }

        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
<div class="input-group-append">
<span class="input-group-text">{$property['append']}</span>
</div>
EOD;
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }
        $html = <<<EOT
        <div class="input-group">
        {$prepend}
        <input type="number" class="form-control" name="{$key}" value="{$value}" data-default="{$default}"${readonly}${placeholder}{$style}  ${onchange} />
        {$append}
        </div>
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
