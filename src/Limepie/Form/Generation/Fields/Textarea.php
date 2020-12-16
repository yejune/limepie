<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Textarea extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $html = <<<EOT
        <textarea class="form-control" name="{$key}" data-default="{$default}" rows="{$rows}">{$value}</textarea>

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
