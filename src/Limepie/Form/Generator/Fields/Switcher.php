<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Switcher extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \is_object($value)) {
            if (true === \property_exists($value, 'property')) {
                $property = [...$property, ...$value->property];
            }

            if (true === \property_exists($value, 'value')) {
                $value = $value->value;
            }
        }

        $sendValue = (string) ($property['value'] ?? 1);
        $value     = (string) $value;
        $default   = '';

        if (true === isset($property['default']) && $property['default']) {
            $default = (string) $property['default'];
        }

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }

        // $default = null;

        // if (true === isset($property['default'])) {
        //     if (true === \is_array($property['default'])) {
        //     } else {
        //         $default = (string) $property['default'];
        //     }
        // }

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $prependClass = '';

        if (true === isset($property['prepend_class'])) {
            $prependClass = ' ' . $property['prepend_class'];
        }
        $appendClass = '';

        if (true === isset($property['append_class'])) {
            $appendClass = ' ' . $property['append_class'];
        }

        if (true === isset($property['append'])) {
            if (true === isset($property['append'][static::getLanguage()])) {
                $append = $property['append'][static::getLanguage()];
            } else {
                $append = $property['append'];
            }
        } else {
            $append = '';
        }

        if (true === isset($property['prepend'])) {
            if (true === isset($property['prepend'][static::getLanguage()])) {
                $prepend = $property['prepend'][static::getLanguage()];
            } else {
                $prepend = $property['prepend'];
            }
            $prepend = '<div class="me-2 form-label' . ($prependClass ? ' ' . $prependClass : '') . '">' . $prepend . '</div>';
        } else {
            $prepend = '';
        }

        $onload = false;

        if (true === isset($property['onload']) && $property['onload']) {
            $onload = true;
        }

        $onclick = '';

        // script 테그에 넣으면 dynamic copy시 동작안하므로 inline script 사용
        if (true === isset($property['onclick'])) {
            $onclick = ' onclick="' . \Limepie\minify_js($property['onclick']) . '" ';
        }

        $elementClass = '';

        if (true === isset($property['element_class'])) {
            $elementClass = ' ' . $property['element_class'];
        }
        $inputClass = '';

        if (true === isset($property['input_class'])) {
            $inputClass = ' ' . $property['input_class'];
        }

        $readonly = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            $readonly = ' disabled-group';
        }

        $buttons = '';

        $id = 'switcher-' . \Limepie\clean_str($key);

        $checked = $sendValue === $value ? 'checked' : '';

        // $buttons .= <<<EOD
        //     <input id="{$id}" class="form-check-input{$inputClass}" type="checkbox" name="{$key}" role="switch" autocomplete="off" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$default}" data-is-default=false {$checked} {$onclick}>
        //     <label for="{$id}" class="form-check-label {$elementClass}{$appendClass}"><span>{$append}</span></label>
        // EOD;

        $script = <<<SCR
        <script nonce="{$_SESSION['nonce']}">
        // $(function () {
        //     var f = $('#'+$.escapeSelector( '{$id}'));
        //     if (f[0].onclick) {
        //         console.log('{$id}');
        //         //f.prop("checked", true);
        //         f.trigger("click");
        //     }
        // });
        </script>
        SCR;

        $html = <<<EOT
            <div class="input-group{$readonly}">
                <div class="d-flex">
                    {$prepend}
                    <div class="form-switch form-check mb-0">
                        <input id="{$id}" class="valid-target form-check-input{$inputClass}" type="checkbox" name="{$key}" role="switch" autocomplete="off" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$sendValue}" data-is-default="{$default}" {$checked} {$onclick}>
                    </div>
                    <label for="{$id}" class="form-check-label {$elementClass}{$appendClass} noselect"><span>{$append}</span></label>
                </div>
            </div>
            {$script}
        EOT;

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
