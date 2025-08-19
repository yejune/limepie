<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Juso extends Fields
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

        $uniqueId = 'element_' . \uniqid();

        $first_sort = $property['first_sort'] ?? ''; // 정확도순 정렬(none), 우선정렬(road: 도로명 포함, location: 지번 포함)
        $hstryYn    = $property['hstry_yn']   ?? ''; // 변동된 주소정보 포함 여부

        $count_per_page = $property['count_per_page'] ?? 10;
        $api_key        = $property['api_key']        ?? '';
        $default        = $property['default']        ?? '';
        $default        = \is_array($default) ? '' : $default;
        $readonly       = '';

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

        $prepend = $prependClass = '';

        if (true === isset($property['prepend_class']) && $property['prepend_class']) {
            $prependClass = ' ' . $property['prepend_class'];
        }

        if (true === isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
            <span class="input-group-text{$prependClass}">{$property['prepend']}</span>
            EOD;
        }

        $append = $appendClass = '';

        if (true === isset($property['append_class']) && $property['append_class']) {
            $appendClass = ' ' . $property['append_class'];
        }

        if (true === isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text{$appendClass}">{$property['append']}</span>
            EOD;
        }

        $display = 'display: none;';
        $option  = '';

        if ($value) {
            $display = 'display: block;';
            $json    = \json_decode($value, true);
            $evalue  = \htmlspecialchars($value);

            if ($json) {
                $option = '<option selected value="' . $evalue . '">[' . $json['zipNo'] . '] ' . $json['roadAddr'] . '</option>';
            }
        }

        return <<<EOT
            <div class="input-group">
            {$prepend}
            <input type="text" onkeydown="var that = this;enterJuso(event, function() {getJuso({currentPage:1, countPerPage:{$count_per_page}, resultType: 'json', confmKey: '{$api_key}', hstryYn: '{$hstryYn}', firstSort: '{$first_sort}', keyword_element: $(that)}, 'target_{$uniqueId}');})" class="form-control{$elementClass}" name="view_{$key}" value="" {$readonly}{$disabled}{$placeholder}{$style} />

            <button class="btn btn-juso btn-search" type="button" onclick="getJuso({currentPage:1, countPerPage:{$count_per_page}, resultType: 'json', confmKey: '{$api_key}', hstryYn: '{$hstryYn}', firstSort: '{$first_sort}', keyword_element: $(this).prev()}, 'target_{$uniqueId}')" >&nbsp;</button>
            {$append}
            </div>
            <div id="target_{$uniqueId}" class="juso-container" style='{$display}'>
            <select class="form-select mt-2 valid-target" name="{$key}" value="{$value}" data-name="{$propertyName}" data-rule-name="{$ruleName}">
            <option value=''>검색된 주소를 선택해주세요.</option>{$option}
            </select>
            <div class="juso-loader" style='display: none;'></div>
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
