<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Tagify2 extends Fields
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
        $id     = 'tagify' . \uniqid();
        $server = $property['server'] ?? ''; // '../tagify';
        $value  = \htmlspecialchars((string) $value);

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

        if (isset($property['template']) && 'image' == $property['template']) {
            $tagType  = 'tagify-image';
            $template = <<<'TMPL'
                tag: function(tagData, tagify) {
                    return '<tag title="' + (tagData.title || tagData.value) + '" ' +
                        'contenteditable="false" ' +
                        'spellcheck="false" ' +
                        'tabIndex="' + (this.settings.a11y.focusableTags ? '0' : '-1') + '" ' +
                        'class="' + this.settings.classNames.tag + ' ' + (tagData.class ? tagData.class : "") + '" ' +
                        this.getAttributes(tagData) + '>' +
                        '<x title="" class="' + this.settings.classNames.tagX + '" role="button" aria-label="remove tag"></x>' +
                        '<div>' +
                            '<span class="' + this.settings.classNames.tagText + ' me-1"><img src="' +  tagData.cover_url + '" height="26"></span>' +
                        '</div>' +
                        '</tag>';
                },
                dropdownItem: function(item) {
                    return '<div ' + this.getAttributes(item) +
                           ' class="' + this.settings.classNames.dropdownItem + ' ' + (item.class || "") + '"' +
                           ' tabindex="0" ' +
                           ' role="option"><img src="' +  item.cover_url + '" height="26"></div>';
                },
            TMPL;
        } elseif (isset($property['template']) && 'imagetext' == $property['template']) {
            $tagType = 'tagify-imagetext';

            $template = <<<'TMPL'
                tag: function(tagData, tagify) {
                    return '<tag title="' + (tagData.title || tagData.value) + '" ' +
                        'contenteditable="false" ' +
                        'spellcheck="false" ' +
                        'tabIndex="' + (this.settings.a11y.focusableTags ? '0' : '-1') + '" ' +
                        'class="' + this.settings.classNames.tag + ' ' + (tagData.class ? tagData.class : "") + '" ' +
                        this.getAttributes(tagData) + '>' +
                        '<x title="" class="' + this.settings.classNames.tagX + '" role="button" aria-label="remove tag"></x>' +
                        '<div>' +
                            '<span class="' + this.settings.classNames.tagText + '"><img src="' +  tagData.cover_url + '" height="26"></span>' +
                            ' <span class="tagify__tag-text ms-1 me-1">'+ tagData.value+'</span>'+
                        '</div>' +
                        '</tag>';
                },
                dropdownItem: function(item) {
                    return '<div ' + this.getAttributes(item) +
                           ' class="' + this.settings.classNames.dropdownItem + ' ' + (item.class || "") + '"' +
                           ' tabindex="0" ' +
                           ' role="option"><img src="' +  item.cover_url + '" height="26" class="">' +
                           ' <span class="tagify__tag-text ms-1 me-1">'+ item.value+'</span>'+
                           '</div>';
                },
            TMPL;
        }

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

        // if (true === isset($property['append_class']) && $property['append_class']) {
        //     $appendClass = ' ' . $property['append_class'];
        // }

        // if (true === isset($property['append']) && $property['append']) {
        //     $append = <<<EOD
        //     <span class="input-group-text{$appendClass}">{$property['append']}</span>
        //     EOD;
        // }

        $script = <<<SCRIPT
        <script nonce="{$_SESSION['nonce']}">
        $(function () {
            var inputElem = document.getElementById('{$id}');
            var tagify = new ExtendedTagify2(inputElem, {
                mode: 'search',
                sortable: true,
                template: '{$tagType}',
                position: 'manual',
                server: '{$server}',
            });
        });
        </script>
        SCRIPT;

        return <<<EOT
        <div class="form-control {$tagType}" style="padding:1px 0px" onclick="$('.tagify__input',this).elementFocus();">{$prepend}<input type="text" id="{$id}" class="valid-target {$elementClass}" name="{$key}" value="{$value}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}"{$readonly}{$disabled}{$placeholder}{$style} />{$append}{$script}<div class="dropdown-container"></div></div>
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
