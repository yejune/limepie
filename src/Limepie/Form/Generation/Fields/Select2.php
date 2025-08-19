<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Select2 extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        if (true === \is_array($value)) {
            $value = \key($value);
        } elseif (0 === \strlen((string) $value)) {
            $value = $property['default'] ?? '';
        }

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

        // \pr($dotKey, $key, $property);
        $class = '';

        if (true === isset($property['element_class'])) {
            $class = ' ' . $property['element_class'];
        }

        $expend  = '';
        $scripts = '';

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
                $disabled2 = '';

                if (true === \in_array($itemValue, $disables, false)) {
                    $disabled2 = 'disabled="disabled"';
                } else {
                    $disabled2 = $disabled;
                }

                $content = '';

                if (isset($property['contents'][$itemValue])) {
                    $content = 'data-content="' . $property['contents'][$itemValue] . '"';
                }

                if ((string) $value === (string) $itemValue) {
                    $option .= '<option value="' . $itemValue . '" ' . $content . ' selected="selected"' . $disabled2 . '>' . $itemText . '</option>';
                } else {
                    $option .= '<option value="' . $itemValue . '" ' . $content . ' ' . $disabled2 . '>' . $itemText . '</option>';
                }
            }
        } else {
            $option = '<option value="">select</option>';
        }
        $onchange = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            $onchange = "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'";
        } elseif (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \trim(\addcslashes($property['onchange'], '"')) . '"';
        }

        return <<<EOT
        <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<!-- (Optional) Latest compiled and minified JavaScript translation files -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/i18n/defaults-*.min.js"></script>
        <div class="input-group">
        {$prepend}
        <select class="form-control{$class}" {$style} name="{$key}" data-rule-name="{$ruleName}" {$onchange} data-default="{$default}">{$option}</select>
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
