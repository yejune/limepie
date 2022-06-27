<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Geometry extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        //$value = \htmlspecialchars((string) $value);

        $result = \json_decode($value, true);

        $geometry = '';
        //\pr($value);

        if ($result) {
            $geometry = '(' . $result[0]['geometry']['location']['lat'] . ' ' . $result[0]['geometry']['location']['lng'] . ')';
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
<div class="input-group-prepend">
<span class="input-group-text">{$property['prepend']}</span>
</div>
EOD;
        }

        $placeholder = '';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = ' placeholder="' . $property['placeholder'] . '"';
        }

        $geometryId = 'f' . \uniqid();

        if ($geometry) {
            $callback = $geometryId . '_initMap';
        }
        $display = 'display: none;';

        if ($value) {
            $display = 'display: block;';
        }

        return <<<EOT

<script src="/assets/googlemap/jquery.googlemap.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBlMmLIXhf24iAAXMeXGllYsZOTkc9bgtM"></script>


<div class='input-group' style='position:relative'>
    <input class="form-control" id="address{$geometryId}" name="{$key}" data-rule-name="{$ruleName}" value="{$value}">

    <span class="btn-group input-group-btn">
    <button class="btn btn-geometry" type="button" id="submit{$geometryId}"><span class="fas fa-search"></span></button>
    </span>

    <div class="input-group mt-1" id="map{$geometryId}" style="{$display} width: 100%; height: 300px;"></div>
    <input type="hidden" id='geometry{$geometryId}' value="">
</div>
<script>
$(function() {
    $("#submit{$geometryId}").on('click', function() {
        $("#map{$geometryId}").address({
            value: $('#address{$geometryId}').val(),
            success: function(e, elements, results) { // callback
                $("#geometry{$geometryId}").val('{lat: '+e.lat+', lng:'+e.lon+'}');
                console.log(e, results);
                $('#map{$geometryId}').css('display', 'block');
            }
        });
    });

    $("#map{$geometryId}").googleMap({
        zoom : 15,
        coords : [37.49795662483599, 127.02758405711666],
        type : "ROADMAP", // SATELLITE, HYBRID, TERRAIN
        debug : false,
        language : "english",
        overviewMapControl: false,
        streetViewControl: false,
        scrollwheel: false,
        mapTypeControl: false,
        marker : {
            coords: [37.49795662483599, 127.02758405711666],
            address: "", // or an address
            draggable: true,
            success: function(e, element, results) { // callback
                $("#geometry{$geometryId}").val('{lat: '+e.lat+', lng:'+e.lon+'}');

                $("#map{$geometryId}").geometry({
                    value: {lat: e.lat, lng: e.lon},
                    success: function(results) { // callback
                        $('#address{$geometryId}').val(results[0].formatted_address)
                        console.log(e, results);


                        var form = $('#address{$geometryId}').closest( "form" )[ 0 ];
                        var validator = $.data( form, "validator" );
                        validator.isFocus = true;
                        validator.elementValid2($('#address{$geometryId}'));
                    }
                });
            }
        }
    });

    // var mark{$geometryId} = ;

    // $("#map{$geometryId}").addMarker(mark{$geometryId});
});
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
