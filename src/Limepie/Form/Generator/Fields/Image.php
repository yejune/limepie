<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\arr;
use Limepie\Form\Generator\Fields;

class Image extends Fields
{
    public static function write($key, $property, $data, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $accept     = $property['rules']['accept']    ?? 'image/*';
        $maxWidth   = $property['max-width']          ?? 0;
        $minWidth   = $property['min-width']          ?? 0;
        $maxHeight  = $property['max-height']         ?? 0;
        $minHeight  = $property['min-height']         ?? 0;
        $viewWidth  = $property['preview-max-width']  ?? 0;
        $viewHeight = $property['preview-max-height'] ?? 0;

        $imageSizeAttribute = '';

        $style = '';

        if ($viewWidth) {
            $style              .= 'max-width:' . $viewWidth . 'px';
            $imageSizeAttribute .= ' width=' . $viewWidth;
        }

        if ($viewHeight) {
            $style              .= 'max-height:' . $viewHeight . 'px';
            $imageSizeAttribute .= ' height=' . $viewHeight;
        }

        if ($style) {
            $style = 'style="' . $style . '"';
        }

        $prepend = $prependClass = '';

        if (true === isset($property['prepend_class']) && $property['prepend_class']) {
            $prependClass = ' ' . $property['prepend_class'];
        }

        if (true === isset($property['prepend']) && $property['prepend']) {
            $prependText = $property['prepend'];

            if (\is_array($property['prepend']) && $property['prepend'][\Limepie\get_language()]) {
                $prependText = $property['prepend'][\Limepie\get_language()];
                // } else {
                //     $prependText = '';
            }

            $prepend = <<<EOD
            <span class="input-group-text{$prependClass}">{$prependText}</span>
            EOD;
        }

        if (true === arr::is_file_array($data, false)) {
            $value  = \htmlspecialchars((string) $data['name']);
            $button = '';
            $html   = <<<EOT
            <div class="input-group">{$prepend}<input type="text" class='form-control form-control-file' value="{$value}" readonly="readonly" />
            EOT;

            foreach ($data as $key1 => $value1) {
                if (\in_array($key1, ['name', 'type', 'size', 'tmp_name', 'error', 'full_path', 'file_name_alias_seq', 'url'])) {
                    if ('name' === $key1) {
                        $html .= <<<EOT
                        <input type="text" class='valid-target form-control-file form-control-filetext form-control-image' data-max-width="{$maxWidth}" data-min-width="{$minWidth}" data-max-height="{$maxHeight}" data-min-height="{$minHeight}" data-preview-max-width="{$viewWidth}" data-preview-max-height="{$viewHeight}" name="{$key}[{$key1}]" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value1}" accept="{$accept}" />
                        EOT;
                    } else {
                        if ('tmp_name' === $key1) {
                        } else {
                            $html .= <<<EOT
                            <input type="hidden" class="clone-element" name="{$key}[{$key1}]"  value="{$value1}" />
                            EOT;
                        }
                    }
                }
            }

            $html .= <<<EOT
            <!--btn--></div>
            <div class='form-preview clone-element'><div><a href="{$data['url']}" target="_new"><img {$style} src='{$data['url']}' class='form-preview-image'></a></div></div>
            EOT;
            $button = <<<'EOT'
            <button class="btn btn-search btn-file-search-text" type="button">&nbsp;</button>
            EOT;
        } else {
            $value = '';
            $html  = <<<EOT
            <div class="input-group">{$prepend}<input type="text" class='form-control form-control-file' value="" readonly="readonly" /><input type="file" class='valid-target form-control-file form-control-image' data-max-width="{$maxWidth}" data-min-width="{$minWidth}" data-max-height="{$maxHeight}" data-min-height="{$minHeight}" data-preview-max-width="{$viewWidth}" data-preview-max-height="{$viewHeight}" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value}" accept="{$accept}" /><!--btn--></div>
            EOT;
            $button = <<<'EOT'
            <button class="btn btn-search btn-file-search" type="button">&nbsp;</button>
            EOT;
        }

        return [$html, $button];
    }

    public static function read($key, $property, $data)
    {
        $html = '';

        if (true === arr::is_file_array($data, false)) {
            $value = \str_replace('', '', (string) $data['path']);
            $html  = <<<EOT
            <img src="{$value}" />

EOT;
        }

        return $html;
    }
}
