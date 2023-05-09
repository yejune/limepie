<?php

declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Select extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
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

        if (isset($property['expend']) && $property['expend']) {
            $dotKey = \str_replace(['[', ']'], ['.', ''], $key);

            $parts = \explode('.', $dotKey);
            // $dotParts   = [];
            $keyAsArray = [];

            foreach ($parts as $part) {
                if (1 === \preg_match('#__([^_]{13,})__#', $part)) {
                    $keyAsArray[] = $part;
                    // $dotParts[]   = '*';
                }
                // $dotParts[] = $part;
            }
            // $dotKey2 = \implode('.', $dotParts);

            $keyName = \addcslashes($key, '[]');

            $parts2      = \explode('.', $property['expend']);
            $dot2        = [];
            $keyAsArray2 = $keyAsArray;

            foreach ($parts2 as $part2) {
                if ('*' === $part2) {
                    $keyAs3 = \array_shift($keyAsArray2);
                    $dot2[] = $keyAs3;
                } else {
                    $dot2[] = $part2;
                }
            }
            $keyAsUnderbar     = \implode('_', $dot2);
            $targetElementName = \addcslashes(static::getNameByArray($dot2), '[]');
            // \pr($property['childs']);

            $childs = \json_encode($property['childs'], \JSON_UNESCAPED_UNICODE);
            $scripts .= <<<EOD
<script>
$(function() {
    var {$keyAsUnderbar}_childs = {$childs}
    $('[name="{$keyName}"]').change(function() {
        $('[name="{$targetElementName}"]').empty();
        if('undifined' !== typeof {$keyAsUnderbar}_childs[this.value]) {
            //var tmp = '';
            for(var i in {$keyAsUnderbar}_childs[this.value]) {
                var child = {$keyAsUnderbar}_childs[this.value][i];
                var option = $('<option value="'+i+'">'+child+'</option>');

                if(i) {
                    $('[name="{$targetElementName}"]').append(option);
                } else {
                    $('[name="{$targetElementName}"]').prepend(option);
                }
                //tmp += '<option value="'+i+'">'+child+'</option>';
            }
            //console.log(tmp);
            $('[name="{$targetElementName}"]').val('');
            $('[name="{$targetElementName}"]').removeAttr('readonly');
            // $('[name="{$targetElementName}"]').removeAttr('onFocus');
            // $('[name="{$targetElementName}"]').removeAttr('onChange');
            // $('[name="{$targetElementName}"]').off('focus');
            // $('[name="{$targetElementName}"]').off('change');

            var form = $( this ).closest( "form" )[ 0 ];
            var validator = $.data( form, "validator" );
            if(validator) {
                validator.loadvalid();
            }
        } else {
        }
    });
});
</script>
EOD;

            // \pr($key, $dotKey2, static::getNameByArray($dot2));

            // exit;

            $expend = <<<'EOD'

EOD;
        }
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
            if ($property['items'] instanceof \Resource\Helper\Menu) {
                $option = '<option value="">select..</option>';
                // storage of output

                try {
                    $output = new \ArrayIterator();
                    // create the caching iterator of the nav array
                    $its = new \RecursiveIteratorIterator(
                        new \Limepie\RecursiveIterator\AdjacencyList($property['items']->menu),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );

                    // child flag
                    $depth = 0;

                    // generate the nav
                    foreach ($its as $it) {
                        // set the current depth
                        $curDepth = $its->getDepth();

                        // store the difference in depths
                        $diff = \abs($curDepth - $depth);
                        // close previous nested levels
                        if ($curDepth < $depth) {
                            $output->append(\str_repeat('</optgroup>', $diff));
                        }

                        $depth = $its->getDepth();

                        $path = [];

                        for ($depth = $its->getDepth(); $depth && $depth--;) {
                            \array_unshift($path, $its->getSubIterator($depth)->current()['name']);
                        }

                        $path[] = $it['name'];

                        // check if we have the last nav item

                        // either add a subnav or close the optionst item
                        if ($its->hasChildren()) {
                            $output->append('<optgroup label="' . \str_repeat('&nbsp;', $curDepth * 4) . $it['name'] . '">');
                        } else {
                            $selected = '';

                            if ((string) $value === (string) $it['params']['seq']) {
                                $selected = " selected='selected'";
                            }
                            $space = '';

                            if (0 < $curDepth) {
                                $space = \str_repeat('&nbsp;', ($curDepth - 1) * 4);
                            }
                            $output->append('<option value="' . $it['params']['seq'] . '" ' . $selected . '>' . $space . \implode(' > ', $path) . '</option>');
                        }

                        // cache the depth
                        $depth = $curDepth;
                    }

                    // if we have values, output the unordered list
                    if ($output->count()) {
                        $option .= \implode("\n", (array) $output);
                    }
                } catch (\Exception $e) {
                    exit($e->getMessage());
                }
            } else {
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
        $html = <<<EOT
        <div class="input-group">
        {$prepend}
        <select class="form-control{$class}" {$style} name="{$key}" data-rule-name="{$ruleName}" {$onchange} data-default="{$default}">{$option}</select>
        {$append}
        </div>
        {$scripts}
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        if (\is_array($value)) {
            $value = $property['default'] ?? '';
        } else {
            if (0 === \strlen((string) $value)) {
                $value = $property['default'] ?? '';
            }
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
