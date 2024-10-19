<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Datetime extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if ($value) {
            $value = \date('Y-m-d\TH:i:s', \strtotime($value));
        }

        $default = $property['default'] ?? '';

        if (!$value && $default) {
            $value = \date('Y-m-d\TH:i:s', \strtotime($default));
        }

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }
        $style = '';

        if (isset($property['style']) && $property['style']) {
            $style = ' style="' . $property['style'] . '"';
        }

        $onchange = '';

        if (isset($property['event'])) {
            $function = '';
            $type     = [];

            if (isset($property['event']['function'])) {
                $function = $property['event']['function'];
            }

            if (isset($property['event']['type'])) {
                $type = $property['event']['type'];
            }

            if ($type) {
                foreach ($type as $event) {
                    if ('onchange' === $event) {
                        $onchange .= ' onchange="' . \Limepie\minify_js(\str_replace('"', '\"', $function)) . '"';
                    }

                    if ('onload' === $event) {
                        $onchange .= ' data-onload="' . \Limepie\minify_js(\str_replace('"', '\"', $function)) . '"';
                    }
                }
            }
        }

        if (isset($property['onchange']) && $property['onchange']) {
            $onchange .= ' onchange="' . \Limepie\minify_js(\str_replace('"', '\"', $property['onchange'])) . '"';
        }

        return <<<EOT
        <input type="datetime-local" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}"{$readonly}{$onchange}{$style} />
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
