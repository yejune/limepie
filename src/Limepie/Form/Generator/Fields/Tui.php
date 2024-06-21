<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Tui extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        // $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            // $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $class   = $property['element_class'] ?? '';
        $linkcss = $property['linkcss']       ?? '';

        if ($linkcss) {
            $linkcss = '<link rel="stylesheet" href="' . $linkcss . '"></link>';
        }

        if (true === isset($property['preview']) && $property['preview']) {
            $previewStyle = $property['preview'];
        } else {
            $previewStyle = 'vertical';
        }

        if (true === isset($property['edit']) && $property['edit']) {
            $editStyle = $property['edit'];
        } else {
            $editStyle = 'wysiwyg';
        }
        // $image = false;

        // if (true === isset($property['image']) && $property['image']) {
        //     $image = true;
        // }

        if (true === isset($property['fileserver']) && $property['fileserver']) {
            $fileserver = $property['fileserver'];
        } else {
            $fileserver = '';
        }
        $uuid = \Limepie\random_uuid();

        if (\preg_match_all('#(?P<eids>__[a-zA-Z0-9]{13}__)#', '__6288d7db50b82__' . $key, $match)) {
            $id = \Limepie\array_last($match['eids']);
        } else {
            $id = \uniqid();
        }

        if (false === \strpos($key, ']')) {
            $uuidkey = $key . '_uuid';
        } else {
            $uuidkey = \preg_replace('#\]$#', '_uuid]', $key);
        }

        if ($fileserver) {
            $imageButton = '';
        } else {
            $imageButton = "#tui{$id} .tui-image{
                display: none !important;
            }";
        }

        $tuid = \trim($id, '_');

        return <<<EOT
        <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
        <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
        {$linkcss}

        <input type="hidden" class="tui_ids" value="{$tuid}">
        <input type="hidden" name="{$uuidkey}" value="{$uuid}">
        <textarea class="form-control d-none" id="textarea_html{$id}" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}" >{$value}</textarea>
        <div id="tui{$id}" class="form-control {$class} tui-body" style="display:block; width: 100%">{$value}</div>
        <style>#tui{$id} .te-mode-switch-section {
        //  display: none !important;
        //  height: 0;
        }
        {$imageButton}
        </style>
<script>
$(function() {
    tui("{$id}", {
        fileserver: "{$fileserver}"
    });
});
</script>
EOT;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);

        return <<<EOT
        {$value}
EOT;
    }
}
