<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Text extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        if (true === \is_array($value)) {
            $value = '';
        }
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default']) && false === \is_array($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default  = $property['default'] ?? '';
        $default  = \is_array($default) ? '' : $default;
        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= ' pointer-events: none;';
        }

        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        $disabled = '';

        if (isset($property['disabled']) && $property['disabled']) {
            $disabled = ' disabled="disabled"';
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }
        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
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

        return <<<EOT
        <div class="input-group">
        {$prepend}
        <input type="text" class="form-control{$elementClass}" name="{$key}" value="{$value}" data-rule-name="{$ruleName}" data-default="{$default}"{$readonly}{$disabled}{$placeholder}{$style} />
        {$append}
        </div>
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
