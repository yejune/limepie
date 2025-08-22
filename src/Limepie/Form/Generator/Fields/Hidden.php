<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Hidden extends Fields
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

        if (0 === \strlen($value)) {
            if (true === isset($property['uuid']) && true === $property['uuid']) {
                $value = \Limepie\uuid();
            }
        }

        $id = $property['id'] ?? '';

        if (0 === \strlen($id)) {
            $id = 'id="' . $id . '"';
        }

        return <<<EOT
        <input type="hidden" class="valid-target form-control" readonly="readonly" name="{$key}" {$id} data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value}"  data-default="{$default}" id="{$id}" />
        EOT;
    }

    public static function read($key, $property, $value)
    {
        return $html = '';
    }
}
