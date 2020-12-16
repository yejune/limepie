<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Search extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';

        $html = <<<EOT
        <input type="text" class="form-control form-control-search" readonly="readonly" value="{$value}" />
        <input type="hidden" name="{$key}" value="{$value}" data-default="{$default}" />
EOT;

        $button = <<<EOT
        <button class="btn btn-search" type="button"><span class="fas fa-search"></span></button>
EOT;

        return [$html, $button];
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
