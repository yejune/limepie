<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Dummy extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        // $value = \htmlspecialchars((string) $value);

        // if (0 === \strlen($value) && true === isset($property['default'])) {
        //     $value = \htmlspecialchars((string) $property['default']);
        // }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }

        if ($value) {
            $value = \nl2br($value);
        }
        $html = <<<EOT
        <div class="{$elementClass}">{$value}</div>

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
