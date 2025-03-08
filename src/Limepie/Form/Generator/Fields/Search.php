<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Di;
use Limepie\Form\Generator\Fields;

class Search extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        // \prx($property);

        // if (true === \is_array($value)) {
        //     $value = null;
        // }
        if (true === \is_array($value) && $value) {
            if (false === isset($property['items']) || !$property['items']) {
                $item = [
                    'text' => $value['search_text'],
                    'id'   => $value['search_id'],
                ];

                if (isset($value['search_cover_url'])) {
                    $item['cover_url'] = $value['search_cover_url'];
                }

                if (isset($value['append_text'])) {
                    $item['append_text'] = $value['append_text'];
                }

                if (isset($value['prepend_text'])) {
                    $item['prepend_text'] = $value['prepend_text'];
                }
                $property['items'][$value['search_id']] = $item;
            }
            $value = $value['search_id'];
        } else {
            if (isset($property['model']) && $property['model']) {
                // \prx($property['model'], $value);

                $tableName = '\Resource\Model\Database\Service\\' . \Limepie\camelize($property['model']['table']);
                $model     = new $tableName();

                foreach ($property['model']['relations'] ?? [] as $rel) {
                    $relationTableName = '\Resource\Model\Database\Service\\' . \Limepie\camelize($rel['table']);
                    $match             = new $relationTableName();
                    $matchKey          = 'match' . \Limepie\camelize($rel['left']) . 'With' . \Limepie\camelize($rel['right']);
                    $match->{$matchKey}();
                    $model->relation($match);
                }
                $dataset = $value;

                if ($value) {
                    $slave1  = Di::getMysqlSlave1();
                    $dataset = $model($slave1)->getBySeq($value)->toArray(function ($data) use ($value, $property) {
                        $table  = $property['model']['table'];
                        $result = '';

                        // \prx($data);

                        foreach ($property['model']['keys'] as $key) {
                            if ($key['table'] === $table) {
                                $result .= ($key['prepend'] ?? '') . $data[$key['field']] . ($key['append'] ?? '');
                            } else {
                                $result .= ($key['prepend'] ?? '') . $data[$key['table'] . '_model'][$key['field']] . ($key['append'] ?? '');
                            }
                        }

                        return  [
                            'id'   => $value,
                            'text' => $result,
                        ];
                    });
                }
                // $model->getList([
                //     'limit' => 100,
                // ]);

                $property['items'][$value] = $dataset;
            } else {
                $property['items'][$value] = $value;
            }
        }

        // \pr($ruleName, $property['rule_name'] ?? '');
        if (0 === \strlen((string) $value)) {
            $value = $property['default'] ?? '';
        }
        // $id = 'id' . \time() . '_' . \str_replace(['[', ']'], ['_', ''], $key);

        $id = \Limepie\clean_str($key) . '_' . \uniqid();

        $value    = \htmlspecialchars((string) $value);
        $default  = $property['default']  ?? '';
        $disabled = $property['disabled'] ?? '';
        $disables = $property['disables'] ?? [];

        if ($disabled) {
            $disabled = 'disabled="disabled"';
        }
        // $dotKey = \preg_replace('#.__([^_]{13})__#', '[]', \str_replace(['[', ']'], ['.', ''], $key));

        $style = '';

        if (true === isset($property['readonly']) && $property['readonly']) {
            if (false === isset($property['element_style'])) {
                $property['element_style'] = '';
            }
            $property['element_style'] .= '-webkit-appearance: none; -moz-appearance: none; text-indent: 1px;text-overflow: \'\'; pointer-events: none;';
        }

        if (isset($property['element_style']) && $property['element_style']) {
            $style = ' style="' . $property['element_style'] . '"';
        }

        $api_server = '';

        if (isset($property['api_server']) && $property['api_server']) {
            $api_server = \Limepie\minify_js($property['api_server']);
        }

        $placeholder = 'keyword';

        if (isset($property['placeholder']) && $property['placeholder']) {
            $placeholder = $property['placeholder'];
        }

        $keyword_min_length = 2;

        if (isset($property['keyword_min_length']) && $property['keyword_min_length']) {
            $keyword_min_length = $property['keyword_min_length'];
        }
        $hide_searching = 2;

        if (isset($property['hide_searching']) && $property['hide_searching']) {
            $hide_searching = $property['hide_searching'];
        }
        $delay = 250;

        if (isset($property['delay']) && $property['delay']) {
            $delay = $property['delay'];
        }

        // \pr($dotKey, $key, $property);
        $class = '';

        if (true === isset($property['element_class'])) {
            $class = ' ' . $property['element_class'];
        }

        $callback = '';

        if (true === isset($property['callback'])) {
            $callback = <<<SCRIPT
            $('#{$id}').on('select2:select', {$property['callback']});
            SCRIPT;
        }

        $multiple = 0;

        if (true === isset($property['multiple']) && $property['multiple']) {
            $multiple = 1;
        }

        $prepend = '';

        if (isset($property['prepend']) && $property['prepend']) {
            $prepend_class = '';

            if (isset($property['prepend_class']) && $property['prepend_class']) {
                $prepend_class = ' ' . $property['prepend_class'];
            }
            $prepend = <<<EOD
            <span class="input-group-text {$prepend_class}">{$property['prepend']}</span>
            EOD;
        }

        $append = '<button type="button" class="btn btn-outline-secondary text-nowrap">리셋</button>';
        $append = '';

        if (isset($property['append']) && $property['append']) {
            $append = <<<EOD
            <span class="input-group-text">{$property['append']}</span>
            EOD;
        }

        $containerClass = '';

        if (!$prepend) {
            $containerClass .= ' input-group-first';
        }

        if (!$append && !($property['multiple'] ?? false)) {
            $containerClass .= ' input-group-last';
        }

        $option = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemKeyValue => $itemValue) {
                $coverUrl    = '';
                $itemText    = '';
                $prependText = '';
                $appendText  = '';

                if (isset($itemValue['id'])) {
                    $coverUrl    = $itemValue['cover_url'] ?? '';
                    $itemText    = $itemValue['text'];
                    $prependText = $itemValue['prepend_text'] ?? '';
                    $appendText  = $itemValue['append_text']  ?? '';
                } else {
                    $itemText = $itemValue;
                }

                if (true === \is_array($itemText)) {
                    if (true === isset($itemText[\Limepie\get_language()])) {
                        $itemText = $itemText[\Limepie\get_language()];
                    }
                }

                $optionDisabled = '';

                if (true === \in_array($itemKeyValue, $disables, false)) {
                    $optionDisabled = 'disabled="disabled"';
                } else {
                    $optionDisabled = $disabled;
                }
                // \pr($value, $itemKeyValue);

                $optionClass = ''; // $containerClass;

                if (isset($itemValue['class']) && $itemValue['class']) {
                    $optionClass = ' ' . $itemValue['class'];
                }

                if ((string) $value === (string) $itemKeyValue) {
                    $option .= '<option data-prepend-text="' . $prependText . '" data-append-text="' . $appendText . '" data-cover-url="' . $coverUrl . '" value="' . $itemKeyValue . '" selected="selected"' . $optionDisabled . ' data-class="' . $optionClass . '">' . $itemText . '</option>';
                } else {
                    $option .= '<option data-prepend-text="' . $prependText . '" data-append-text="' . $appendText . '" data-cover-url="' . $coverUrl . '" value="' . $itemKeyValue . '" ' . $optionDisabled . ' data-class="' . $optionClass . '">' . $itemText . '</option>';
                }
            }
        } else {
            $option = '<option value="">select</option>';
        }
        // \prx($option);
        $onchange = '';

        if (true === isset($property['onchange'])) {
            $onchange = 'onchange="' . \Limepie\minify_js($property['onchange']) . '"';
        } elseif (true === isset($property['readonly']) && $property['readonly']) {
            $onchange = "readonly onFocus='this.initialSelect = this.selectedIndex;' onChange='this.selectedIndex = this.initialSelect;'";
        }

        $styleText = '';

        if ($hide_searching) {
            $styleText = "<style nonce=\"{$_SESSION['nonce']}\">.{$id}_select2 .loading-results { display: none; }</style>";
        }

        $scripts = <<<SCRIPT
        <script nonce="{$_SESSION['nonce']}">$(function() {select2('{$id}', '{$keyword_min_length}', '{$delay}', '{$containerClass}');{$callback}});</script>
        SCRIPT;

        return <<<EOT
        {$styleText}{$scripts}<div class="input-group">{$prepend}<select class="valid-target form-control{$class}" {$style} name="{$key}" data-class="{$containerClass}" data-keyword-min-length="{$keyword_min_length}" data-delay="{$delay}" data-api-server="{$api_server}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  id="{$id}" {$onchange} data-default="{$default}" class="testselect">{$option}</select>{$append}<!--btn--></div>
        EOT;
    }

    public static function read($key, $property, $value)
    {
        if (0 === \strlen($value)) {
            $value = $property['default'] ?? '';
        }

        $value = \htmlspecialchars((string) $value);

        $option = '';

        if (true === isset($property['items'])) {
            foreach ($property['items'] as $itemKeyValue => $itemText) {
                $itemKeyValue = \htmlspecialchars((string) $itemKeyValue);

                if ($value === $itemKeyValue) {
                    $option .= $itemText;
                }
            }
        }

        return <<<EOT
        {$value}

EOT;
    }
}
