<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Time extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if ($value) {
            $value = \date('H:i', \strtotime($value));
        }

        $default = $property['default'] ?? '';

        if (!$value && $default) {
            $value = \date('H:i', \strtotime($default));
        }

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $onchange = '';

        if (isset($property['onchange']) && $property['onchange']) {
            $onchange = ' onchange="' . \Limepie\minify_js(\str_replace('"', '\"', $property['onchange'])) . '"';
        }

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
            <span class="input-group-text">{$property['prepend']}</span>
            EOD;
        }

        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text">{$property['append']}</span>
            EOD;
        }
        $min = '';

        if (isset($property['rules']['min']) && $property['rules']['min']) {
            $min = ' min="' . $property['rules']['min'] . '"';
        }

        $max = '';

        if (isset($property['rules']['max']) && $property['rules']['max']) {
            $max = ' max="' . $property['rules']['max'] . '"';
        }

        return <<<EOT
        <div class="input-group">{$prepend}<input type="time" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}"{$readonly}{$onchange}{$min}{$max} />{$append}</div>
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
