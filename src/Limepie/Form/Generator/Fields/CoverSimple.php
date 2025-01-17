<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

// file-name-alias-seq, url로 구성된다.
class CoverSimple extends Fields
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
            $prepend = <<<EOD
            <span class="input-group-text{$prependClass}">{$property['prepend']}</span>
            EOD;
        }

        $inputType = 'file';

        if ($data) {
            $inputType = 'text';
        }
        // $value  = \htmlspecialchars((string) $data['name']);
        $data                        = $data ?: [];
        $data['url']                 = $data['url']                 ?? '';
        $data['file_name_alias_seq'] = $data['file_name_alias_seq'] ?? '';

        $button = '';

        $html = <<<EOT
            <input type="text" class='form-control form-control-file url' value="{$data['url']}" readonly="readonly" />
            EOT;
        $html .= <<<EOT
            <input type="{$inputType}" class='valid-target form-control-file form-control-filetext form-control-image url' data-max-width="{$maxWidth}" data-min-width="{$minWidth}" data-max-height="{$maxHeight}" data-min-height="{$minHeight}" data-preview-max-width="{$viewWidth}" data-preview-max-height="{$viewHeight}" name="{$key}[name]" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$data['url']}" accept="{$accept}" />
            EOT;

        $html .= <<<EOT
            <input type="hidden" class="clone-element file_name_alias_seq" name="{$key}[file_name_alias_seq]"  value="{$data['file_name_alias_seq']}" />
            EOT;
        $html .= <<<EOT
            <input type="hidden" class="clone-element url" name="{$key}[url]"  value="{$data['url']}" />
            EOT;

        if ($data['url']) {
            $html .= <<<EOT
            <div class='form-preview clone-element'><div><a href="{$data['url']}" target="_new"><img {$style} src='{$data['url']}' class='form-preview-image'></a></div></div>
            EOT;
        }

        $button = <<<'EOT'
            <button class="btn btn-search btn-file-search-text" type="button">&nbsp;</button>
            EOT;

        return [$html, $button];
    }

    public static function read($key, $property, $data)
    {
        $html = '';

        if (true === \Limepie\arr\is_file_array($data, false)) {
            $value = \str_replace('', '', (string) $data['path']);
            $html  = <<<EOT
            <img src="{$value}" />

EOT;
        }

        return $html;
    }
}
