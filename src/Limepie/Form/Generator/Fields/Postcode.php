<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Postcode extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \is_array($value)) {
            $json = $value;
        } else {
            $json = \json_decode($value, true);
        }

        $addr      = ''; // 주소 변수
        $extraAddr = ''; // 참고항목 변수
        $zonecode  = ''; // 우편번호

        if ($json) {
            [$addr, $extraAddr, $zonecode] = \Limepie\get_daum_postcode($json);
        }

        $default = $property['default'] ?? '';
        $keyName = \addcslashes($key, '[]');

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $disabled = '';

        if (isset($property['disabled']) && $property['disabled']) {
            $disabled = ' disabled="disabled"';
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }

        $id = 'f' . \uniqid();

        return <<<EOT

        <div class='input-group input-group-postcode col-md-5' style="width: 200px !important;">
            <span class="input-group-text">우편번호</span>
            <input type="text" class="postcode form-control" readonly="readonly" value="{$zonecode}" placeholder="" />
                <button class="btn btn-postcode btn-search" type="button">&nbsp;</button>

        </div>
        <div class="iframe" style="display:none;border:1px solid;margin:5px 0;position:relative">
        <img src="//t1.daumcdn.net/postcode/resource/images/close.png"
        style="cursor:pointer;position:absolute;right:0px;bottom:-1px;z-index:1;width:20px; height: 20px;" alt="접기 버튼" class="btn-close">
    </div>
        <div class="input-group input-group-postcode mt-1 mb-0">
        <span class="input-group-text">주소</span>
        <input type="text" class="form-control address" readonly="readonly" value="{$addr}" />
        <span class="input-group-text">참고항목</span>
        <input type="text" class="form-control extra_address" readonly="readonly" value="{$extraAddr}" />
        </div>

        <input type="hidden" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value='{$value}' data-default="{$default}" class="raw valid-target" id="{$id}_postcode_raw" />


<script src="//ssl.daumcdn.net/dmaps/map_js_init/postcode.v2.js"></script>
<script nonce="{$_SESSION['nonce']}">
var {$id}_postcode_element = new DaumPostcode('#{$id}_postcode_raw');
// console.log({$id}_postcode_element );
</script>
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
