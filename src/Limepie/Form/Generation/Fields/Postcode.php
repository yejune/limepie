<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

use Limepie\Form\Generation\Fields;

class Postcode extends Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        $json = \json_decode($value, true);

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

        $zoncode      = $json['zonecode']     ?? '';
        $roadAddress  = $json['roadAddress']  ?? '';
        $jibunAddress = $json['jibunAddress'] ?? '';

        return <<<EOT
        <div class='input-group input-group-postcode col-md-5'>
            <div class="input-group-prepend">
            <span class="input-group-text">우편번호</span>
            </div>
            <input type="text" class="form-control" readonly="readonly" value="{$zoncode}" id="{$id}_postcode" placeholder="" />
            <span class="btn-group input-group-btn">
                <button class="btn btn-postcode" type="button" onclick="{$id}_execDaumPostcode()"><span data-feather="search"></span></button>
            </span>
        </div>

        <div class="input-group input-group-postcode mt-1 mb-1">
            <div class="input-group-prepend">
            <span class="input-group-text">도로명 주소</span>
            </div>
            <input type="text" class="form-control address_road" readonly="readonly" value="{$roadAddress}" id="{$id}_road" />
        </div>

        <div class="input-group input-group-postcode ">
            <div class="input-group-prepend">
            <span class="input-group-text">지번 주소</span>
            </div>
            <input type="text" class="form-control address_jibun" readonly="readonly" value="{$jibunAddress}" id="{$id}_jibun" />
        </div>

        <input type="hidden" name="{$key}" data-rule-name="{$ruleName}" value='{$value}' data-default="{$default}" />


<script src="//ssl.daumcdn.net/dmaps/map_js_init/postcode.v2.js"></script>
<script>
    function {$id}_execDaumPostcode() {
        new daum.Postcode({
            oncomplete: function(data) {
                $('[name="{$keyName}"]').val(JSON.stringify(data));

                var form = $('[name="{$keyName}"]').closest( "form" )[ 0 ];
                var validator = $.data( form, "validator" );
                if(validator) {
                    validator.loadvalid();
                }

                // 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분.

                // 도로명 주소의 노출 규칙에 따라 주소를 표시한다.
                // 내려오는 변수가 값이 없는 경우엔 공백('')값을 가지므로, 이를 참고하여 분기 한다.
                var roadAddr = data.roadAddress; // 도로명 주소 변수
                var extraRoadAddr = ''; // 참고 항목 변수

                // 법정동명이 있을 경우 추가한다. (법정리는 제외)
                // 법정동의 경우 마지막 문자가 "동/로/가"로 끝난다.
                if(data.bname !== '' && /[동|로|가]$/g.test(data.bname)){
                    extraRoadAddr += data.bname;
                }
                // 건물명이 있고, 공동주택일 경우 추가한다.
                if(data.buildingName !== '' && data.apartment === 'Y'){
                    extraRoadAddr += (extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName);
                }
                // 표시할 참고항목이 있을 경우, 괄호까지 추가한 최종 문자열을 만든다.
                if(extraRoadAddr !== ''){
                    extraRoadAddr = ' (' + extraRoadAddr + ')';
                }

                JSON.stringify(data)

                // 우편번호와 주소 정보를 해당 필드에 넣는다.
                document.getElementById('{$id}_postcode').value = data.zonecode;
                document.getElementById("{$id}_road").value = roadAddr;
                document.getElementById("{$id}_jibun").value = data.jibunAddress;

                // // 참고항목 문자열이 있을 경우 해당 필드에 넣는다.
                // if(roadAddr !== ''){
                //     document.getElementById("{$id}_extraAddress").value = extraRoadAddr;
                // } else {
                //     document.getElementById("{$id}_extraAddress").value = '';
                // }

                // var guideTextBox = document.getElementById("guide");
                // // 사용자가 '선택 안함'을 클릭한 경우, 예상 주소라는 표시를 해준다.
                // if(data.autoRoadAddress) {
                //     var expRoadAddr = data.autoRoadAddress + extraRoadAddr;
                //     guideTextBox.innerHTML = '(예상 도로명 주소 : ' + expRoadAddr + ')';
                //     guideTextBox.style.display = 'block';

                // } else if(data.autoJibunAddress) {
                //     var expJibunAddr = data.autoJibunAddress;
                //     guideTextBox.innerHTML = '(예상 지번 주소 : ' + expJibunAddr + ')';
                //     guideTextBox.style.display = 'block';
                // } else {
                //     guideTextBox.innerHTML = '';
                //     guideTextBox.style.display = 'none';
                // }
            }
        }).open();
    }

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
