<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

class Textarea extends \Limepie\Form\Generator\Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        return <<<EOT
            <textarea class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}" rows="{$rows}">{$value}</textarea>

        EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);

        return <<<EOT
        {$value}

EOT;
    }
}
