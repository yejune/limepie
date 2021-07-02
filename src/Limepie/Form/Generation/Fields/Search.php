<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Search extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if (0 === \strlen((string) $value)) {
            $value = $property['default'] ?? '';
        }
        $id = \str_replace(['[', ']'], ['_', ''], $key);

        $value    = \htmlspecialchars((string) $value);
        $default  = $property['default']  ?? '';
        $disabled = $property['disabled'] ?? '';
        $disables = $property['disables'] ?? [];

        if ($disabled) {
            $disabled = 'disabled="disabled"';
        }
        // $dotKey = \preg_replace('#.__([^_]{13})__#', '[]', \str_replace(['[', ']'], ['.', ''], $key));

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

        $api_server = '';

        if (isset($property['api_server']) && $property['api_server']) {
            $api_server = $property['api_server'];
        }

        $placeholder = 'keyword';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = $property['placeholder'];
        }

        // \pr($dotKey, $key, $property);
        $class = '';

        if (true === isset($property['element_class'])) {
            $class = ' ' . $property['element_class'];
        }

        $expend  = '';
        $scripts = <<<SCRIPT
            <script>
                $(function() {
                    $(document).on('click', '.select2-results__message', function() {
                      $('#{$id}').val(null).trigger('change');
                    });
                    $("#{$id}").select2({
                        placeholder: "{$placeholder}",
                        language: {
                            errorLoading: function () {
                                return "결과를 읽을수 없습니다."
                            }, inputTooLong: function (e) {
                                var t = e.input.length - e.maximum, n = "Please delete " + t + " character";
                                return t != 1 && (n += "s"), n
                            }, inputTooShort: function (e) {
                                var t = e.minimum - e.input.length, n = "Please enter " + t + " or more characters";
                                return n
                            }, loadingMore: function () {
                                return "다음 결과를 읽는 중입니다."
                            }, maximumSelected: function (e) {
                                var t = "You can only select " + e.maximum + " item";
                                return e.maximum != 1 && (t += "s"), t
                            }, noResults: function () {
                                return "결과가 없습니다."
                            }, searching: function () {
                                return "검색중입니다."
                            }, removeAllItems: function () {
                                return "Remove all items"
                            }
                        },
                        ajax: {
                            url: {$api_server},
                            method: "post",
                            dataType: "json",
                            delay: 250
                        },
                        minimumInputLength: 2
                    });
                });

            </script>
        SCRIPT;

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
<div class="input-group-prepend">
<span class="input-group-text">{$property['prepend']}</span>
</div>
EOD;
        }

        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
<div class="input-group-append">
<span class="input-group-text">{$property['append']}</span>
</div>
EOD;
        }
        $option = '';

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
                            $option .= '<option value="' . $subItemValue . '" selected="selected"' . $disabled2 . '>' . $itemValue . ' > ' . $subItemText . '</option>';
                        } else {
                            $option .= '<option value="' . $subItemValue . '" ' . $disabled2 . '>' . $itemValue . ' > ' . $subItemText . '</option>';
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

        if (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \trim(\addcslashes($property['onchange'], '"')) . '"';
        } elseif (true === isset($property['readonly']) && $property['readonly']) {
            $onchange = "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'";
        }

        return <<<EOT
        <div class="input-group">
        {$prepend}
        <select class="form-control{$class}" {$style} name="{$key}" id="{$id}" {$onchange} data-default="{$default}" class="testselect">{$option}</select>
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
