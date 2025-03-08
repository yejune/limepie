<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Textarea extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default   = $property['default']       ?? '';
        $rows      = $property['rows']          ?? 5;
        $className = $property['element_class'] ?? '';
        $counter   = '';
        $maxLength = '';
        $readonly  = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }
        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            // $property['element_style'] .= ' pointer-events: none;';
        }

        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
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

        if (
            isset($property['counter']) && $property['counter']
                                        && isset($property['rules']['maxlength']) && $property['rules']['maxlength']
        ) {
            $className .= ' textarea-counter-wrap';
            $maxLength = ' maxlength="' . $property['rules']['maxlength'] . '"';
            $counter   = '<p class="position-absolute no-select bottom-0 end-0 m-1 textarea-counter fs-7 mb-0" style="color:#939393;">(<span pan class="counter text-black">0/' . $property['rules']['maxlength'] . '</span>bytes)</p>';
        }

        $innerBtn = '';

        if (true === isset($property['inner_btn']) && $property['inner_btn']) {
            $innerBtn = '<!--btn-->';
        }

        return <<<EOT
        <div class="input-group">{$prepend}<textarea class="valid-target form-control {$className}" {$readonly} {$style} name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}" rows="{$rows}"{$maxLength}>{$value}</textarea>{$counter}{$append}{$innerBtn}</div>
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
