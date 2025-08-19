<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

// 지원안함, radio는 element특성상 이름이 동일하므로 generate에서 검증이 애매함, select로 대체해서 사용할것
class xRadio extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $showhide = '';

        if (true === isset($property['showhide'])) {
            $showhide = <<<EOT
                onclick="showhide(this, '{$property['showhide']}')"
            EOT;
        }
        $html = '';

        foreach ($property['items'] as $radioValue => $radioText) {
            if (true === \is_array($radioText)) {
                if (true === isset($radioText[\Limepie\get_language()])) {
                    $radioText = $radioText[\Limepie\get_language()];
                }
            }

            $checked = $radioValue === $value ? 'checked="checked"' : '';

            $html .= <<<EOT
                <label><input type="radio" class="valid-target form-control" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$radioValue}" {$checked} {$showhide} />{$radioText}</label>

            EOT;
        }

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;

        return <<<EOT
        {$value}

EOT;
    }
}
