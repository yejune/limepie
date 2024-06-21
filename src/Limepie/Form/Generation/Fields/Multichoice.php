<?php

declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Multichoice extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        // $value = (string) $value;

        if (!$value) {
            $value = [];
        }

        if (0 === \count($value) && true === isset($property['default'])) {
            $value = [(string) $property['default']];
        }
        $actives = $property['active'] ?? [];

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

        $buttons = '';
        $scripts = '';

        if (true === isset($property['items']) && true === \is_array($property['items'])) {
            foreach ($property['items'] as $k1 => $v1) {
                if (true === \is_array($v1)) {
                    if (true === isset($v1[\Limepie\get_language()])) {
                        $v1 = $v1[\Limepie\get_language()];
                    }
                }

                $active = '';

                if (true === \in_array($k1, $actives, false)) {
                    $checked = 'checked';
                    $active .= 'dactive ';
                }

                if (true === \in_array($k1, $value, false)) {
                    $checked = 'checked';
                    $active .= 'active';
                } else {
                    $checked = '';
                    $active .= '';
                }
                $buttons .= <<<EOD
<label class="btn btn-switch btn-swich-checkbox {$active}">
<input type="checkbox" name="{$key}" data-rule-name="{$ruleName}" autocomplete="off" value="{$k1}" {$checked} {$onchange}> {$v1}
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
                // pr(static::$conditions[$dotKey2]);
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
    $('[name="{$keyName}"]').change(function() {
{$script}
        var form = $( this ).closest( "form" )[ 0 ];
        var validator = $.data( form, "validator" );
        validator.elementValid(this);
    });
});
</script>
EOD;
            $html = <<<EOT
            <div class="xbtn-group flex-wrap btn-group-toggle" data-toggle="buttons">
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

        return <<<EOT
        {$value}

EOT;
    }
}
