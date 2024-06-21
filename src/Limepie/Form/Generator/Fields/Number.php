<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Number extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \is_object($value)) {
            $property = [...$property, ...$value->property];
            $value    = (float) $value->value;
        }

        if (true === \is_array($value)) {
            $value = '';
        }

        // if (true === \is_array($value)) {
        //     \pr($key, $value);
        // }
        if (0 < \strlen((string) $value)) {
            $value = (float) $value;
        }

        if (0 === \strlen((string) $value) && true === isset($property['default'])) {
            $value = (float) $property['default'];
        }
        $default  = $property['default'] ?? '';
        $readonly = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $disabled = '';

        if (true === isset($property['disabled']) && $property['disabled']) {
            $disabled = ' disabled="disabled"';
        }

        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }

        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= ' pointer-events: none;';
        }

        if (true === isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        $onchange = '';

        if (true === isset($property['onchange'])) {
            $onchange .= ' onchange="' . \Limepie\minify_js($property['onchange']) . '"';
            // $onchange .= ' onkeyup="' . \Limepie\minify_js($property['onchange']) . '"';
        }

        $prepend = $prependClass = '';

        if (true === isset($property['prepend_class']) && $property['prepend_class']) {
            $prependClass = ' ' . $property['prepend_class'];
        }

        if (true === isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
            <span class="input-group-text{$prependClass}">{$property['prepend']}</span>
            EOD;
        }

        $append = $appendClass = '';

        if (true === isset($property['append_class']) && $property['append_class']) {
            $appendClass = ' ' . $property['append_class'];
        }

        if (true === isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text{$appendClass}">{$property['append']}</span>
            EOD;
        }

        $placeholder = '';

        if (true === isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }

        if (
            true    === isset($property['rules']['min'])
            && true === isset($property['rules_not_match']) // rule과 맞지 않으면 value를 null로 처리한다.
            && 'clear' == $property['rules_not_match']
        ) {
            if ($value < $property['rules']['min']) {
                $value = null;
            }
        }

        return <<<EOT
            <div class="input-group">
            {$prepend}
            <input type="number" class="valid-target form-control{$elementClass}" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value}" data-default="{$default}"{$readonly}{$disabled}{$placeholder}{$style} {$onchange} />
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
