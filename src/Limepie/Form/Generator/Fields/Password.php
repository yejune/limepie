<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Password extends Fields
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

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $autocomplete = '';

        if (isset($property['autocomplete']) && $property['autocomplete']) {
            $autocomplete = ' autocomplete="' . $property['autocomplete'] . '"';
        }

        return <<<EOT
        <input type="password" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="" data-default="{$default}"{$readonly}{$autocomplete} />
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
