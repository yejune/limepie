<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Tagify extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \is_object($value)) {
            $property = [...$property, ...$value->property];
            $value    = $value->value;
        }

        if (true === \is_array($value)) {
            $value = '';
        }
        $id         = 'tagify' . \uniqid();
        $delimiters = $property['delimiters'] ?? ',';
        $tag        = $property['tag']        ?? '';
        $value      = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default']) && false === \is_array($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default  = $property['default'] ?? '';
        $default  = \is_array($default) ? '' : $default;
        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= ' pointer-events: none;';
        }

        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        $disabled = '';

        if (isset($property['disabled']) && $property['disabled']) {
            $disabled = ' disabled="disabled"';
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        } else {
            $placeholder = ' placeholder="search item"';
        }
        $elementClass = '';

        if (isset($property['element_class']) && $property['element_class']) {
            $elementClass = ' ' . $property['element_class'];
        }

        $template = '';
        $tagType  = 'tagify-text';

        $prepend = $prependClass = '';

        // if (true === isset($property['prepend_class']) && $property['prepend_class']) {
        //     $prependClass = ' ' . $property['prepend_class'];
        // }

        // if (true === isset($property['prepend']) && $property['prepend']) {
        //     $prepend = <<<EOD
        //     <span class="input-group-text{$prependClass}">{$property['prepend']}</span>
        //     EOD;
        // }

        $append = $appendClass = '';

        if ($tag) {
            $option = <<<EOD
                ,
                transformTag: function(tagData) {
                    tagData.value = tagData.value.replace(/[@#,\$\\%\\(\\)\\[\\]\\{\\}\\;\\:'"\\/`]/g, '');

                    return tagData;
                },
                originalInputValueFormat: function(valuesArr) {
                    return valuesArr.map(function(item) {
                        return '{$tag}' + item.value;
                    }).join(' ');
                },
                templates: {
                    tag: function(tagData) {
                        if(!tagData.value ) return false;
                        return '<tag title="' + tagData.value + '" contenteditable="false" spellcheck="false" tabindex="-1" class="' + this.settings.classNames.tag + (tagData.class ? ' ' + tagData.class : '') + '" ' + this.getAttributes(tagData) + '>' +
                            '<x title="" class="' + this.settings.classNames.tagX + '" role="button" aria-label="remove tag"></x>' +
                            '<div>' +
                                '<span class="' + this.settings.classNames.tagText + '">{$tag}' + tagData.value + '</span>' +
                            '</div>' +
                        '</tag>';
                    }
                }
            EOD;
        } else {
            $option = '';
        }
        $script = <<<SCRIPT
        <script>
        $(function () {
            var inputElem = document.getElementById('{$id}');
            new Tagify(inputElem, {
                delimiters: '{$delimiters}'
                {$option}
            });
            var el = $(inputElem).prev()[0];
            el.classList.remove('valid-target');
            Sortable.create(el, {
                draggable: '.tagify__tag',
                onUpdate: function (/**Event*/evt) {
                    $(evt.from).next()[0].__tagify.updateValueByDOMTags()
                }
            });
        });
        </script>
        SCRIPT;

        return <<<EOT
        <div class="form-control {$tagType}" style="padding:1px 0px">
            {$prepend}
            <input type="text" id="{$id}" class="valid-target {$elementClass}" name="{$key}" value="{$value}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}"{$readonly}{$disabled}{$placeholder}{$style} />
            {$append}
            {$script}
        </div>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;

        return <<<EOT
        {$value}

        EOT;
    }
}
