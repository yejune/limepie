<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Password extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        return <<<EOT
        <input type="password" class="form-control" name="{$key}" data-rule-name="{$ruleName}" value="" data-default="{$default}"{$readonly} />

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
