<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Hidden extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';

        if (0 === \strlen($value)) {
            if (true === isset($property['uuid']) && true === $property['uuid']) {
                $value = \Limepie\uuid();
            }
        }

        return <<<EOT
    <input type="hidden" class="form-control" readonly="readonly" name="{$key}" data-rule-name="{$ruleName}" value="{$value}" data-default="{$default}" />
EOT;
    }

    public static function read($key, $property, $value)
    {
        return $html = '';
    }
}
