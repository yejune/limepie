<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class SelectModal extends Fields
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

        $value      = \htmlspecialchars((string) $value);
        $selectText = '';
        $default    = $property['default']  ?? '';
        $disabled   = $property['disabled'] ?? '';
        $disables   = $property['disables'] ?? [];

        if ($disabled) {
            $disabled = 'disabled="disabled"';
        }

        $style = '';

        // if (true === isset($property['readonly']) && $property['readonly']) {
        //     if (false === isset($property['element_style'])) {
        //         $property['element_style'] = '';
        //     }
        //     $property['element_style'] .= '-webkit-appearance: none; -moz-appearance: none; text-indent: 1px;text-overflow: \'\'; pointer-events: none;';
        // }

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
            <span class="input-group-text">{$property['prepend']}</span>
            EOD;
        }

        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text">{$property['append']}</span>
            EOD;
        }

        $onchange = '';
        $script   = ';';

        if (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \Limepie\minify_js($property['onchange']) . '"';
            $script .= \Limepie\minify_js($property['onchange']);
        }
        $id = \Limepie\clean_str($key) . '_' . \uniqid();

        $html = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemValue => $item) {
                $check      = '';
                $checkClass = '';
                $clickid    = $id . '_radio_' . \Limepie\genRandomString();
                $itemText   = $item['name'];

                if ($item['short_description'] ?? false) {
                    $itemText .= ' (' . $item['short_description'] . ')';
                }

                if ($value == $itemValue) {
                    $check      = "checked='checked'";
                    $selectText = $itemText;
                    $checkClass = 'select-modal-selected';
                }
                $dataAttributes = [];

                foreach ($item['data'] ?? [] as $dataKey => $dataValue) {
                    // $dataValue        = \addslashes((string) $dataValue);
                    $dataAttributes[] = 'data-' . \str_replace('_', '-', $dataKey) . "='{$dataValue}'";
                }

                $dataAttribute = \implode(' ', $dataAttributes);

                $html .= \str_replace(PHP_EOL, '', <<<HTML
                    <label class="select-modal-label {$checkClass}">
                        <div class="fs-5 mb-2 d-flex align-items-center">
                            <input type="radio" name="select-modal{$id}" value="{$itemValue}" onclick="selectModalItem(this, '{$itemValue}', '{$itemText}');{$script}" data-bs-dismiss="modal" {$dataAttribute} {$check}>
                            <span class="title ms-1">{$itemText}</span>
                        </div>
                        <div class="border border-2 rounded rounded-3 p-3">
                            <div class="">
                                <img src="{$item['cover_url']}" width="50%">
                            </div>
                        </div>
                    </label>
                HTML);
            }
        } else {
            $html = 'no items.';
        }

        return <<<EOT
        <div class="input-group select-modal-wrap"><div class="modal fade" id="select-modal{$id}" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="select-modal-label{$id}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h1 class="modal-title fs-5" id="select-modal-label">{$property['label']}.</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">{$html}</div><div class="modal-footer d-none"><button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary">선택하세요.</button></div></div></div></div><input type="hidden" name="{$key}" class="valid-target select-modal-value" id="value_{$id}" data-name="{$propertyName}" data-rule-name="{$ruleName}" value="{$value}"><div class="input-group dropdown-icon">{$prepend}<input type="text" id="target_{$id}" class="select-modal-text form-control cursor-pointer bg-white  {$class}"  {$style} placeholder="선택하세요." readonly  data-bs-toggle="modal" data-bs-target="#select-modal{$id}" value="{$selectText}">{$append}</div></div>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        if (0 === \strlen($value)) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $html = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $value => $itemText) {
                $value = \htmlspecialchars((string) $value);

                if ($value === $value) {
                    $html .= $itemText;
                }
            }
        }

        return <<<EOT
        {$value}

EOT;
    }
}
