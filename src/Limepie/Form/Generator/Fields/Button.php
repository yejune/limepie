<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Button extends Fields
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
        $default = $property['default'] ?? '';
        $id      = \str_replace(['[', ']'], ['_', ''], $key);

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }
        $onclick = '';

        if (isset($property['onclick']) && $property['onclick']) {
            $onclick = $property['onclick'];
        }
        $class = '';

        if (isset($property['button_class']) && $property['button_class']) {
            $class = ' ' . $property['button_class'];
        }

        $init_script = '';

        if (isset($property['init_script']) && $property['init_script']) {
            $init_script = $property['init_script'];
        }

        return <<<EOT
            <script>
            $(function() {
                {$init_script}
                $("#btn{$id}").on('click', function() {
                    {$onclick}
                });
            });
            </script>
            <input type="hidden" class="valid-target form-control" readonly="readonly" name="{$key}"  data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}" />
            <input type="button" class="btn{$class}" name="btn{$key}" id="btn{$id}" value="{$property['text']}" {$readonly}/>

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
