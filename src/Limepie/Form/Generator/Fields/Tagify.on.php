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
        $id     = 'tagify' . \uniqid();
        $server = $property['server'] ?? '../tagify';
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
        <script>
        function updateDropdownPosition(tagify) {
            var position = limepie.common.getElementAbsolutePosition(tagify.DOM.scope);
            var cssText = "left: " + position.left + "px; " +
            "top: " + position.bottom + "px; " +
            "width: 100%px";


            tagify.DOM.dropdown.style.cssText = cssText;

            var dropdownContainer = tagify.DOM.scope.parentNode.querySelector('.dropdown-container');
            if (dropdownContainer) {
                dropdownContainer.appendChild(tagify.DOM.dropdown);
            }
        }
        $(function () {
            var inputElem = document.getElementById('{$id}');
            var controller;
            var tagify = new Tagify(inputElem, {
                whitelist: [],
                backspace: false,
                addTagOn: [],
                editTags: false,
                originalInputValueFormat: function (valuesArr) {
                    return valuesArr.map(function (item) {
                        return item.seq;
                    }).join(',');
                },
                dropdown: {
                    position: 'manual',
                    maxItems: 50,
                    classname: 'tags-look',
                    enabled: 0,
                    closeOnSelect: false
                },
                templates: {
                    {$template}
                    dropdownFooter: function (suggestions) {
                        var hasMore = suggestions.length;
                        var footerHTML = '';

                        footerHTML = '<footer data-selector="tagify-suggestions-footer" class="' + this.settings.classNames.dropdownFooter + '">';
                        footerHTML += hasMore + ' items.';
                        footerHTML += '</footer>';

                        return footerHTML;
                    }
                },
            });

            var el = $(inputElem).prev()[0];
            el.classList.remove('valid-target');
            var tags = $('.tagify__tag', el);

            Sortable.create(el, {
                draggable: '.tagify__tag',
                onUpdate: function (/**Event*/evt) {
                    $(evt.from).next()[0].__tagify.updateValueByDOMTags()
                }
            });
            tagify.on("dropdown:show", function() {
                updateDropdownPosition(tagify);
                console.log(tagify.DOM.dropdown);
            });

            window.addEventListener('resize', function() {
                // 드롭다운이 현재 표시되고 있는지 확인 후 업데이트
                if (tagify.DOM.dropdown.style.display !== 'none') {
                    updateDropdownPosition(tagify);
                }
            });
            tagify.on('keydown', function(e){
                console.log('keydown',e);

                if(tagify.DOM.input.value && tagify.DOM.input.value.length == 0) {
                    setTimeout(function() {
                        console.log('keydown');
                        tagify.DOM.dropdown.remove();

                    }, 100);
                }
            });
            tagify.on('input', function(e){
                var value = e.detail.value;
                tagify.whitelist = null;
                // if (value.length == 0) {
                //     setTimeout(function() {
                //         tagify.DOM.dropdown.remove();

                //     }, 100);
                // }
                controller && controller.abort()
                controller = new AbortController()

                tagify.loading(true)
                if(value.length) {
                    console.log('fetch');
                    fetch('{$server}?value=' + value, {signal:controller.signal})
                    .then(function(RES) {
                        return RES.json();
                    })
                    .then(function(newWhitelist) {
                        if(newWhitelist) {
                            tagify.whitelist = newWhitelist;
                            tagify.loading(false).dropdown.show(value);
                        } else {
                            tagify.whitelist = null;
                            tagify.loading(false).dropdown.hide(value);
                        }
                    });
                } else {
                    setTimeout(function() {
                        tagify.DOM.dropdown.remove();

                    }, 100);


                    console.log('input',value, value.length);
                }
            });
            tagify.on('remove', function(e){
                console.log('remove');
            });
            tagify.on('focus', function (e) {
                var currentInput = e.detail.tagify.DOM.input.textContent;
                if (currentInput) {
                    tagify.dropdown.show(currentInput);
                    console.log("Current input on focus:", currentInput);
                }
            });
            tagify.on('add', function(e){
                // console.log(
                //     tagify.dropdown.filterListItems('')
                // );
                // if (0 == tagify.dropdown.filterListItems('').length) {
                //     tagify.dropdown.hide();
                //     tagify.DOM.dropdown.remove();
                // }
                setTimeout(function () {
                    var parent = tagify.DOM.dropdown;
                    var items = parent.querySelectorAll('.tagify__dropdown__item:not(.tagify__dropdown__item--hidden)');

                    if (items.length === 0) {
                        tagify.dropdown.hide();
                        tagify.DOM.dropdown.remove();
                        tagify.DOM.dropdown.style.display = 'none'; // 드롭다운을 숨깁니다.
                        tagify.DOM.input.innerHTML = "";
                    }
                }, 100);
            });
        });
        </script>
        SCRIPT;

        return <<<EOT
        <style>
        .dropdown-container {
            margin-top: 2px;
            border-top: 1px solid #000;
            padding-top: 0px;
        }
        .dropdown-container:empty {
            display: none;
        }
        .tags-look {

        }
        .tagify {
            border:0 !important;
            align-items: center;
        }
        .tagify__dropdown__wrapper {
            box-shadow: none;
            background: transparent !important;
        }
        .tagify__tag-text {
            vertical-align: middle;
        }
        .tagify-imagetext .tags-look .tagify__dropdown__item {
            padding: 0;
        }
        .tagify-imagetext .tagify__tag>div {
            padding: 0;
            overflow: hidden;
        }
        .tagify__dropdown__item .img {
            overflow: hidden;
        }

        .tagify-imagetext .tags-look .tagify__dropdown__item {
            display: inline-table;
            overflow: hidden;
        }
        .tagify-imagetext .tags-look .tagify__dropdown__item--hidden {
            display: none;
        }
        </style>
        <div class="form-control {$tagType}" style="padding:1px 0px" onclick="$('.tagify__input',this).elementFocus();">
            {$prepend}
            <input placeholder='write some tags' type="text" id="{$id}" class="valid-target {$elementClass}" name="{$key}" value="{$value}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}"{$readonly}{$disabled}{$placeholder}{$style} />
            {$append}
            {$script}
            <div class="dropdown-container"></div>
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
