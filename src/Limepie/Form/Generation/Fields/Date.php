<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Date extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if ($value) {
            $value = \date('Y-m-d', \strtotime($value));
        }

        $default = $property['default'] ?? '';

        if(!$value && $default) {
            $value = \date('Y-m-d', \strtotime($default));
        }

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
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

        $html = <<<EOT
        <div class="input-group">
        {$prepend}
        <input type="date" class="form-control" name="{$key}" value="{$value}" data-default="{$default}"${readonly} />
        {$append}
        </div>
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
