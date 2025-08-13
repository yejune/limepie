<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class CheckContainer extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        // $value = (string) $value;

        if (!$value) {
            $value = [];
        }

        if (0 === \count($value) && true === isset($property['default'])) {
            $value = [(string) $property['default']];
        }

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $elementClass = '';

        if (true === isset($property['element_class'])) {
            $elementClass = ' ' . $property['element_class'];
        }

        $onchange = '';

        if (true === isset($property['onchange'])) {
            $onchange = ' onchange="' . \Limepie\minify_js($property['onchange']) . '"';
        }

        $buttons = '';

        if (true === isset($property['items']) && true === \is_array($property['items'])) {
            $index   = 0;
            $prepend = 'mchoice-' . \Limepie\clean_str($key);

            foreach ($property['items'] as $k1 => $v1) {
                ++$index;

                $id = $prepend . $index;

                if (true === \is_array($v1)) {
                    if (true === isset($v1[\Limepie\get_language()])) {
                        $v1 = $v1[\Limepie\get_language()];
                    }
                }

                if (true === \in_array($k1, $value, false)) {
                    $checked = ' checked';
                } else {
                    $checked = '';
                }

                // $buttons .= <<<EOD
                // <input type="checkbox" name="{$key}" id="{$id}" class="valid-target btn-check" autocomplete="off" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$k1}" {$checked}$onchange><label for="{$id}" class="btn btn-switch btn-mswitch {$elementClass}"><span>{$v1}</span></label>
                // EOD;

                $buttons .= <<<EOD
                    <div class=" check-container-card  "> <label class="check-container-label xhover-effect xcard border w-100 rounded-pill cursor-pointer bg-white" for="{$id}"> <div class="p-1 d-flex align-items-center justify-content-between"> <input type="checkbox" name="{$key}" id="{$id}" class="valid-target btn-check" autocomplete="off" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$k1}" {$checked}$onchange> <div class="check-indicator flex-shrink-0 hover"> <span class="indicator-text fs-7"> </span> <span class="indicator-mark"> ✓ </span> </div> <span class="check-container-text ms-1 text-truncate flex-grow-1 fs-7" title="{$v1}">{$v1}</span>  </div> </label>    </div>
                EOD;
            }

            $html = <<<EOT
            <div xclass="btn-group flex-wrap btn-group-toggle">{$buttons}</div>
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
