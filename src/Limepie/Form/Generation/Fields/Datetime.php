<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Datetime extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if ($value) {
            $value = \date('Y-m-d\TH:i:s', \strtotime($value));
        }

        $default = $property['default'] ?? '';

        if(!$value) {
            $value = \date('Y-m-d\TH:i:s', \strtotime($default));
        }

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $html = <<<EOT
        <input type="datetime-local" class="form-control" name="{$key}" value="{$value}" data-default="{$default}"${readonly} />

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
