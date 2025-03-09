<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Choice extends Fields
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

        $value = (string) $value;

        // if (0 === \strlen($value) && true === isset($property['default'])) {
        //     $value = (string) $property['default'];
        // }

        $default = null;

        if (true === isset($property['default'])) {
            if (true === \is_array($property['default'])) {
            } else {
                $default = (string) $property['default'];
            }
        }

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $onload = false;

        if (true === isset($property['onload']) && $property['onload']) {
            $onload = true;
        }

        $onchange = $onclick = $initload = $changeEvent = '';

        // script 테그에 넣으면 dynamic copy시 동작안하므로 inline script 사용
        if (true === isset($property['onchange']) && $property['onchange']) {
            $onchange = ' onchange="' . \Limepie\minify_js($property['onchange']) . '"';
            // $changeEvent = \Limepie\minify_js($property['onchange']);
        }

        if (true === isset($property['onclick']) && $property['onclick']) {
            $onclick = ' onclick="' . \Limepie\minify_js($property['onclick']) . '"';
            // $changeEvent = \Limepie\minify_js($property['onchange']);
        }

        if (true === isset($property['data-init-change']) && $property['data-init-change']) {
            $initload = ' data-init-change="true" ';
            // $changeEvent = \Limepie\minify_js($property['onchange']);
        }
        $buttonClass = '';

        if (true === isset($property['button_class']) && $property['button_class']) {
            $buttonClass = ' ' . $property['button_class'];
        }

        $elementClass = '';

        if (true === isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }
        $inputClass = '';

        if (true === isset($property['input_class']) && $property['input_class']) {
            $inputClass = ' ' . $property['input_class'];
        }

        $readonly = '';

        $readonlyItems = [];

        if (true === isset($property['readonly'])) {
            if (true === \is_array($property['readonly']) && $property['readonly']) {
                foreach ($property['readonly'] as $subKey => $subArray) {
                    $readonlyItems[$subKey] = \array_flip($subArray);
                }

                $readonlyItems = $readonlyItems[$value] ?? [];
            } else {
                $readonly = ' pe-none bg-secondary';
            }
        }

        $buttons = '';
        $script  = '';
        $random  = \Limepie\genRandomString(5);

        if (true === isset($property['items']) && true === \is_array($property['items'])) {
            $index   = 0;
            $prepend = 'choice-' . \Limepie\clean_str($key);

            $defaultId = '';
            $checkedId = '';

            foreach ($property['items'] as $k1 => $v1) {
                ++$index;
                $id = $prepend . '-' . $random . '-' . $index;

                if (true === \is_array($v1)) {
                    if (true === isset($v1[\Limepie\get_language()])) {
                        $v1 = $v1[\Limepie\get_language()];
                    }
                }
                $checkdefault = (string) $default === (string) $k1 ? true : false;

                if ($checkdefault) {
                    $defaultId = $id;
                }

                $checked = (string) $value === (string) $k1 ? ' checked="checked"' : '';

                // if (1 == $property['default'] && false !== \strpos($key, 'is_yoil_price')) {
                //     \pr($key, $default, $value, $k1, $checked);
                // }

                if ($checked) {
                    $checkedId = $id;
                }

                $pe = '';

                if (isset($readonlyItems[$k1]) && \strlen((string) $readonlyItems[$k1]) > 0) {
                    $pe = ' pe-none';
                }
                // \prx($pe, $k1, $readonlyItems[$k1]);

                $buttons .= <<<EOD
                <input id="{$id}" class="valid-target btn-check{$inputClass}" type="radio" name="{$key}" autocomplete="off" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$k1}" data-is-default="{$checkdefault}"{$onchange}{$onclick}{$initload}{$checked}><label for="{$id}" class="btn btn-switch {$elementClass}{$pe}"><span>{$v1}</span></label>
                EOD;

                // if ($changeEvent) {
                //     $script .= <<<SCRIPT
                //         <script>
                //         document.querySelector('input[type="radio"][id="{$id}"]').addEventListener('click', function() {
                //             console.log('click event');
                //             {$changeEvent};
                //         }, true);
                //         </script>
                //     SCRIPT;
                // }
            }

            // $script = '';

            // if ($onload) {
            if ($checkedId) {
                $clickid = $checkedId;
            } else {
                $clickid = $defaultId;
            }

            if ($clickid && $changeEvent) {
                // $script = <<<SCRIPT
                //     <script>
                //     $(function () {
                //         var f = $('#'+$.escapeSelector( '{$clickid}')).on('click', function() {
                //             {$changeEvent};
                //         });
                //     });
                //     </script>
                // SCRIPT;
            }
            // }

            $html = <<<EOT
            <div class="btn-group btn-group-toggle{$readonly}{$buttonClass}" data-toggle="buttons">{$buttons}</div>{$script}
            EOT;
        } else {
            $html = '<input type="text" class="form-control" value="application에서 설정하세요." />';
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
