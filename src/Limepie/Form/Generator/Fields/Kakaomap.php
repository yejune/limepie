<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\ArrayObject;
use Limepie\Di;
use Limepie\Exception;
use Limepie\Form\Generator\Fields;

class Kakaomap extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        // $value = \htmlspecialchars((string) $value);

        // $value    = '""';
        if (true === \is_array($value)) {
            $result      = $value;
            $encodeValue = $encodeValueOrNull = \json_encode($value);
        } elseif ($value instanceof ArrayObject) {
            $result      = $value->attributes;
            $encodeValue = $encodeValueOrNull = \json_encode($value->attributes);
        } else {
            if (0 === \strlen($value)) {
                $value = \json_encode([]);
            }
            $result      = \json_decode($value, true);
            $encodeValue = $encodeValueOrNull = \json_encode($result);
        }

        if ('[]' == $encodeValue || 0 == $encodeValue) {
            $encodeValue       = '';
            $encodeValueOrNull = '[]';
        }

        $level    = 3;
        $address  = '';
        $keyword  = '';
        $geometry = [
            'x' => '33.450701',
            'y' => '126.570667',
        ];
        // \pr($result);
        // $value = '0';
        $method  = 'place.keywordSearch';
        $comment = '//';

        if (isset($property['method']) && $property['method']) {
            if ('address' == $property['method']) {
                $method  = 'geocoder.addressSearch';
                $comment = '';
            } elseif ('keyword' == $property['method']) {
                $method = 'place.keywordSearch';
            }
        }

        $h = '';

        if ('geocoder.addressSearch' == $method) {
            $h = 'd-none';
        }

        if ($result) {
            if (true === isset($result['keyword'])) {
                $keyword = $result['keyword'];
            }

            if (true === isset($result['fixed_address']['road_address']['address_name'])) {
                $address = $result['fixed_address']['road_address']['address_name'];
            } elseif (true === isset($result['fixed_address']['address']['address_name'])) {
                $address = $result['fixed_address']['address']['address_name'];
            } else {
                $address = $result['address_name'];
            }

            if ('geocoder.addressSearch' == $method) {
                $keyword = $address;
            }

            if (true === isset($result['zoom_level'])) {
                $level = $result['zoom_level'];
            }

            if (true === isset($result['fixed'])) {
                $geometry = [
                    'x' => $result['fixed']['x'],
                    'y' => $result['fixed']['y'],
                ];
            } else {
                $geometry = [
                    'x' => $result['x'],
                    'y' => $result['y'],
                ];
            }

            // $geometry = '(' . $result['geometry']['location']['lat'] . ' ' . $result['geometry']['location']['lng'] . ')';
        } else {
            if (0 === \strlen($value) && true === isset($property['default'])) {
                $geometry = (string) $property['default'];
            }
        }
        $callback = '';

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

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend = <<<EOD
                <span class="input-group-text">{$property['prepend']}</span>
            EOD;
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }

        $apikey = '';

        if (Di::getResponse()->getPrivate()->getKakaoLogin(null)?->getClientId(null)) {
            $apikey = Di::getResponse()->getPrivate()?->getKakaoLogin(null)?->getClientId(null);
        }

        if (!$apikey) {
            throw new Exception('kakao api key가 없습니다.');
        }

        $marker_draggable = 'false';
        $comment          = '// ';

        if (isset($property['marker_draggable']) && $property['marker_draggable']) {
            $marker_draggable = 'true';
            $comment          = '';
        }

        $geometryId = 'f' . \uniqid();

        if ($geometry) {
            $callback = $geometryId . '_initMap';
        }
        $display = 'd-none';
        $init    = '';

        if (\count($result)) {
            $display = 'd-block';
            $init    = 'mapinit();';
        }
        // $value = '부산시 해운대구 달맞이길 30';

        return <<<EOT
<script type="text/javascript" src="https://dapi.kakao.com/v2/maps/sdk.js?appkey={$apikey}&libraries=services"></script>
<div class='input-group' style='position:relative'>
    <input class="form-control" id="address{$geometryId}" value="{$keyword}">
</div>
<span class="btn-group input-group-btn">
<button class="btn btn-geometry" type="button" id="submit{$geometryId}">&nbsp;</button>
</span>
<div style='position:relative; width: 100%; height: 300px;' class="mt-1 {$display}" id="wmap{$geometryId}">
    <div class="input-group" id="map{$geometryId}" style="border: 1px solid #d5dae2; position: absolute; width: 100%; height: 300px;"></div>
    <div id="haddress{$geometryId}" class='map_info {$h}' style="position: absolute; z-index: 1">
        {$address}
    </div>
</div>
<textarea style="display: none" id='geometry{$geometryId}' class="valid-target" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" >{$encodeValue}</textarea>

<script nonce="{$_SESSION['nonce']}">
function kakao_map_init{$geometryId}() {
    var mapContainer = document.getElementById('map{$geometryId}'); // 지도를 표시할 div
    var mapOption = {
        center: new kakao.maps.LatLng('{$geometry['y']}', '{$geometry['x']}'), // 지도의 중심좌표, 결과 없을때 표시할 좌표
        level: {$level},
        disableDoubleClickZoom: true,
        scrollwheel: false
    };

    // 지도를 생성합니다
    var map = new kakao.maps.Map(mapContainer, mapOption);

    // 주소-좌표 변환 객체를 생성합니다
    var geocoder = new kakao.maps.services.Geocoder();
    var place    = new kakao.maps.services.Places();
    var coords   = new kakao.maps.LatLng('{$geometry['y']}', '{$geometry['x']}');
    var marker   = new kakao.maps.Marker({
        map: map,
        position: coords
    });
    map.relayout();
    map.setCenter(coords);
    marker.setMap(map);

    var map_results = {$encodeValueOrNull};
    var map_status = null;
    var address_callback = function(result, status) {
        map_results['fixed_address'] = result[0];
        if(result && result.length > 0) {
            if(result[0].road_address) {
                {$comment}$('#address{$geometryId}').val(result[0].road_address.address_name);
                $('#haddress{$geometryId}').html(result[0].road_address.address_name);
            } else {
                {$comment}$('#address{$geometryId}').val(result[0].address.address_name);
                $('#haddress{$geometryId}').html(result[0].address.address_name);
            }
        }
        if(map_results && map_results.length > 0) {
            $("#geometry{$geometryId}").val(JSON.stringify(map_results)).trigger('change');
        }
    };
    var dragend_event_callback = function() {
        // 지도 중심좌표를 얻어옵니다
        var latlng = marker.getPosition();

        coords = new kakao.maps.LatLng(latlng.getLat(), latlng.getLng());
        geocoder.coord2Address(coords.getLng(), coords.getLat(), address_callback);
        //map.setCenter(coords);
        map.panTo(coords);
console.log('map_results', map_results);
        map_results['fixed'] = {
            x: latlng.getLng(),
            y: latlng.getLat()
        };

        $("#geometry{$geometryId}").val(JSON.stringify(map_results)).trigger('change');
    };

    var click_event_callback = function(mouseEvent) {
        // 클릭 좌표를 얻어옵니다
        var latlng = mouseEvent.latLng;

        coords = new kakao.maps.LatLng(latlng.getLat(), latlng.getLng());
        geocoder.coord2Address(coords.getLng(), coords.getLat(), address_callback);
        //map.setCenter(coords);
        map.panTo(coords);

        // 마커 위치를 클릭한 위치로 옮깁니다
        marker.setPosition(latlng);

        map_results['fixed'] = {
            x: latlng.getLng(),
            y: latlng.getLat()
        };

        $("#geometry{$geometryId}").val(JSON.stringify(map_results)).trigger('change');
    };
    var zoom = function() {
        // 지도의 현재 레벨을 얻어옵니다
        var level = map.getLevel();

        map_results['zoom_level'] = level;
        $("#geometry{$geometryId}").val(JSON.stringify(map_results)).trigger('change');
    };

    // 일반 지도와 스카이뷰로 지도 타입을 전환할 수 있는 지도타입 컨트롤을 생성합니다
    var mapTypeControl = new kakao.maps.MapTypeControl();

    // 지도에 컨트롤을 추가해야 지도위에 표시됩니다
    map.addControl(mapTypeControl, kakao.maps.ControlPosition.TOPRIGHT);

    // 지도 확대 축소를 제어할 수 있는  줌 컨트롤을 생성합니다
    var zoomControl = new kakao.maps.ZoomControl();
    map.addControl(zoomControl, kakao.maps.ControlPosition.RIGHT);

    {$comment}kakao.maps.event.addListener(marker, 'dragend',  dragend_event_callback);
    {$comment}kakao.maps.event.addListener(map, 'zoom_changed', zoom);
    {$comment}kakao.maps.event.addListener(map, 'click', click_event_callback);
    marker.setDraggable({$marker_draggable});


    var mapinit = function() {
        // 주소로 좌표를 검색합니다
        if(!$('#address{$geometryId}').val()) {
            alert('장소명, 도로명, 건물명 또는 지번을 입력하세요.');
            $('#address{$geometryId}').focus();
            return false;
        }
        {$method}($('#address{$geometryId}').val(), function(result, status) {
            // 정상적으로 검색이 완료됐으면
            map_status = status;
            if (status === kakao.maps.services.Status.OK) {
                map.setLevel(3);
                coords = new kakao.maps.LatLng(result[0].y, result[0].x);

                // 결과값으로 받은 위치를 마커로 표시합니다
                if(marker) {
                    kakao.maps.event.removeListener(marker, 'dragend', dragend_event_callback);
                    marker.setMap(null)
                }
                marker = new kakao.maps.Marker({
                    map: map,
                    position: coords
                });

                map_results = result[0];

                geocoder.coord2Address(coords.getLng(), coords.getLat(), address_callback);
                map_results['keyword'] = $('#address{$geometryId}').val();
                kakao.maps.event.addListener(marker, 'dragend',  dragend_event_callback);
                marker.setDraggable({$marker_draggable});

                // 인포윈도우로 장소에 대한 설명을 표시합니다
                // var infowindow = new kakao.maps.InfoWindow({
                //     content: '<div style="width:150px;text-align:center;padding:6px 0;">우리회사</div>'
                // });
                // infowindow.open(map, marker);

                $("#geometry{$geometryId}").val(JSON.stringify(result[0])).trigger('change');
                $('#wmap{$geometryId}').addClass('d-block').removeClass('d-none');

                // 지도의 중심을 결과값으로 받은 위치로 이동시킵니다
                map.relayout();
                map.setCenter(coords);
            } else {
                // 결과 없음
                $('#geometry{$geometryId}').val('0').trigger('change');
                $('#wmap{$geometryId}').removeClass('d-block').addClass('d-none');
            }
        });
    };

    $(function() {
        $('#address{$geometryId}').keydown(function() {
            if (event.keyCode === 13) {
            event.preventDefault();
            mapinit();
            };
        });

        //{$init}
        $("#submit{$geometryId}").on('click', mapinit);
    });
}
kakao_map_init{$geometryId}();
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
