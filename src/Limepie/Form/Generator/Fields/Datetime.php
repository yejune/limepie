<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

class Datetime extends \Limepie\Form\Generator\Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
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

        return <<<EOT
            <input type="datetime-local" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}"{$readonly} />

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
