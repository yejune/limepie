<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Choice extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        if(true === is_object($value)) {
            if(true === property_exists($value, 'property')) {
                $property = $value->property + $property;
            }

            if (true === property_exists($value, 'value')) {
                $value = $value->value;
            }
        }
        $value = (string) $value;

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }
        $default  = $property['default'] ?? '';
        $default  = \is_array($default) ? '' : $default;

        $checked = ((bool) $value) ? 'checked' : '';

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $onchange = '';

        if (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \trim(\addcslashes($property['onchange'], '"')) . '"';
        }
        $buttonClass = '';

        if (true === isset($property['button_class'])) {
            $buttonClass = ' ' . $property['button_class'];
        }

        $elementClass = '';

        if (true === isset($property['element_class'])) {
            $elementClass = ' ' . $property['element_class'];
        }

        $readonly = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            $readonly = ' disabled-group';
        }

        $buttons = '';
        $scripts = '';
        $offpossible = '';

        if(true === isset($property['turnoff']) && $property['turnoff'] == true) {
            $offpossible = 'btn-switch-radio-turnoff-possible';
        }

        if (true === isset($property['items']) && true === \is_array($property['items'])) {
            foreach ($property['items'] as $k1 => $v1) {
                if (true === \is_array($v1)) {
                    if (true === isset($v1[\Limepie\get_language()])) {
                        $v1 = $v1[\Limepie\get_language()];
                    }
                }
                $checked = (string) $value === (string) $k1 ? 'checked' : '';
                $active  = (string) $value === (string) $k1 ? 'active' : '';
                $buttons .= <<<EOD
                    <label class="btn btn-switch {$offpossible} {$active} {$elementClass}">
                    <input type="radio" name="{$key}" autocomplete="off" value="{$k1}"  data-default="{$default}"{$checked} ${onchange}> {$v1}
                    </label>

                EOD;
            }
            $dotKey = \str_replace(['[', ']'], ['.', ''], $key);

            $parts      = \explode('.', $dotKey);
            $dotParts   = [];
            $keyAsArray = [];

            foreach ($parts as $part) {
                if (1 === \preg_match('#__([^_]{13})__#', $part)) {
                    $keyAsArray[] = $part;
                    $dotParts[]   = '*';
                } else {
                    $dotParts[] = $part;
                }
            }
            $dotKey2 = \implode('.', $dotParts);

            $keyName = \addcslashes($key, '[]');
            $script  = '';
            if (true === isset(static::$conditions[$dotKey2])) {
                //pr(static::$conditions[$dotKey2]);
                foreach (static::$conditions[$dotKey2] as $k1 => $v1) {
                    foreach ($v1 as $k2 => $v2) {
                        $parts2      = \explode('.', $k2);
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
                        $keyAs2 = \implode('.', $dot2);

                        if ($v2) {
                            $script .= <<<EOD
                                if({$k1} == $(this).val()) {
                                    $('[name="{$keyAs2}.layer"]').removeClass('d-none').addClass('d-block');
                                }

                            EOD;
                        } else {
                            $script .= <<<EOD
                                if({$k1} == $(this).val()) {
                                    $('[name="{$keyAs2}.layer"]').removeClass('d-block').addClass('d-none');
                                }

                            EOD;
                        }
                    }
                }
            }
            $scripts = <<<EOD
                <script>
                $(function() {

                    $('[name="{$keyName}"]').change(function(ev) {
                        ev.preventDefault();
                        {$script}
                        var form = $( this ).closest( "form" )[ 0 ];
                        var validator = $.data( form, "validator" );
                        if(validator) {
                            validator.loadvalid();
                        }
                    });
                });
                </script>
            EOD;

            if (true === isset($property['onchange'])) {
                // 느려서 끔
                // onchage를 호출하지 말고
                // display_target: is_season
                // display_target_condition:
                //   0: "display: block"
                //   1: "display: none"
                // 와 같이 직접적으로 스타일을 적용한다.
                //
                // $scripts .= <<<EOD
                //     <script>
                //     $(function(){
                //         $('[name="{$keyName}"]:checked').eq(0).trigger('change');
                //     });
                //     </script>
                // EOD;
            }
            $html = <<<EOT
                <div class="btn-group btn-group-toggle{$readonly}{$buttonClass}" data-toggle="buttons">
                {$buttons}
                </div>
                {$scripts}
            EOT;
        } else {
            $html = '<input type="text" class="form-control" value="application에서 설정하세요." />';
        }

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;
        $html  = <<<EOT
            {$value}

        EOT;

        return $html;
    }
}
