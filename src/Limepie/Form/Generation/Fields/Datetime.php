<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Datetime extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
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
        <input type="datetime-local" class="form-control" name="{$key}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}"{$readonly} />

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
