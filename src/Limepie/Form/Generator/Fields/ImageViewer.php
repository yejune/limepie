<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class ImageViewer extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        $html = '';

        if (\is_array($value)) {
            foreach ($value ?? [] as $row) {
                $html .= '<img src="' . $row . '" height="' . $property['height'] . '">';
            }
        } else {
            $html = '이미지가 없습니다.';
        }

        return $html;
    }
}
