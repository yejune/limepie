<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Selectbox extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \is_array($value)) {
            $value = \key($value);
        } elseif (0 === \strlen((string) $value)) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $default  = $property['default']  ?? '';
        $disabled = $property['disabled'] ?? '';
        $disables = $property['disables'] ?? [];

        if ($disabled) {
            $disabled = 'disabled="disabled"';
        }
        // $dotKey = \preg_replace('#.__([^_]{13})__#', '[]', \str_replace(['[', ']'], ['.', ''], $key));

        $id = 'id-' . \Limepie\clean_str($key);

        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= '-webkit-appearance: none; -moz-appearance: none; text-indent: 1px;text-overflow: \'\'; pointer-events: none;';
        }

        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        // \pr($dotKey, $key, $property);
        $class = '';

        if (true === isset($property['element_class'])) {
            $class = ' ' . $property['element_class'];
        }

        $scripts = <<<'EOD'
            <script>
            $('.input-group .form-select').on('change', function () {

                if ($(this.options[this.selectedIndex]).closest('optgroup').length) {
                    var label = $(this.options[this.selectedIndex]).closest('optgroup').prop('label');
                    console.log(label);
                    $('.input-group-text-group').text(label).removeClass('d-none');
                } else {
                    $('.input-group-text-group').removeClass('d-none').addClass('d-none');
                }
            });
            </script>
        EOD;

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
            <span class="input-group-text">{$property['prepend']}</span>
            EOD;
        }

        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text">{$property['append']}</span>
            EOD;
        }
        $option         = '';
        $groupName      = '';
        $groupNameClass = 'd-none';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemValue => $itemText) {
                if (true === \is_array($itemText)) {
                    if (true === isset($itemText[\Limepie\get_language()])) {
                        $itemText = $itemText[\Limepie\get_language()];
                    }
                }
                $itemValue = \htmlspecialchars((string) $itemValue);

                if (true === \is_array($itemText)) {
                    $option .= '<optgroup label="' . $itemValue . '">';

                    foreach ($itemText as $subItemValue => $subItemText) {
                        $disabled2 = '';

                        if (true === \in_array($subItemValue, $disables, false)) {
                            $disabled2 = 'disabled="disabled"';
                        } else {
                            $disabled2 = $disabled;
                        }

                        if ((string) $value === (string) $subItemValue) {
                            $groupName      = $itemValue;
                            $groupNameClass = '';
                            $option .= '<option value="' . $subItemValue . '" selected="selected"' . $disabled2 . '>' . $subItemText . '</option>';
                        } else {
                            $option .= '<option value="' . $subItemValue . '" ' . $disabled2 . '>' . $subItemText . '</option>';
                        }
                    }
                    $option .= '</optgroup>';
                } else {
                    $disabled2 = '';

                    if (true === \in_array($itemValue, $disables, false)) {
                        $disabled2 = 'disabled="disabled"';
                    } else {
                        $disabled2 = $disabled;
                    }

                    if ((string) $value === (string) $itemValue) {
                        $option .= '<option value="' . $itemValue . '" selected="selected"' . $disabled2 . '>' . $itemText . '</option>';
                    } else {
                        $option .= '<option value="' . $itemValue . '" ' . $disabled2 . '>' . $itemText . '</option>';
                    }
                }
            }
        } else {
            $option = '<option value="">select</option>';
        }
        $onchange = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            $onchange = "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'";
        } elseif (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \Limepie\minify_js($property['onchange']) . '"';
        }

        return <<<EOT
            <div class="input-group">
            {$prepend}
            <span class="input-group-text input-group-text-group {$groupNameClass}" id="{$id}">{$groupName}</span>
            <select class="valid-target form-select{$class}" {$style} name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  {$onchange} data-default="{$default}">{$option}</select>
            {$append}
            </div>
            {$scripts}
        EOT;
    }

    public static function read($key, $property, $value)
    {
        if (0 === \strlen($value)) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $option = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemValue => $itemText) {
                $itemValue = \htmlspecialchars((string) $itemValue);

                if ($value === $itemValue) {
                    $option .= $itemText;
                }
            }
        }

        return <<<EOT
        {$value}

EOT;
    }
}
