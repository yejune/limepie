<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Div extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        // $value = \htmlspecialchars((string) $value);

        // if (0 === \strlen($value) && true === isset($property['default'])) {
        //     $value = \htmlspecialchars((string) $property['default']);
        // }
        // $default = $property['default'] ?? '';

        $elementClass = ' ';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = $property['element_class'];
        }

        $html = <<<EOT
            <div class="${elementClass}"></div>
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
