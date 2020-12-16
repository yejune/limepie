<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Tui extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        //$value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            //$value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $fileserver = $property['fileserver']    ?? '';
        $class      = $property['element_class'] ?? '';
        $linkcss    = $property['linkcss']       ?? '';

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

        $id     = \uniqid();
        $htmlid = 'html' . $id;

        if (false === \strpos($key, ']')) {
            $htmlkey = $key . '_html';
        } else {
            $htmlkey = \preg_replace('#\]$#', '_html]', $key);
        }
        $html = <<<EOT
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor-contents.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.48.4/codemirror.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/github.min.css"></link>
        {$linkcss}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor-Editor-full.js"></script>

        <textarea class="form-control d-none" id="textarea{$htmlid}" name="{$key}">{$value}</textarea>
        <div id="tui{$id}" class="form-control {$class}"  style="display:block; width: 100%">{$value}</div>
        <style>#tui{$id} .te-mode-switch-section {
        //  display: none !important;
        //  height: 0;
        }</style>
<script>
$(function() {
    var editor = new tui.Editor({
        el: document.querySelector('#tui{$id}'),
        previewStyle: '{$previewStyle}',
        width: '100%',
        height: 'auto',
        initialEditType: '{$editStyle}',
        events: {
            change: function() {
                console.log('is mark', editor.isMarkdownMode());
                // if(editor.isMarkdownMode() == true) {
                //     $('#textarea{$id}').val(editor.getMarkdown());
                // } else {
                //     $('#textarea{$id}').val(editor.getHtml());
                // }
                $('#textarea{$htmlid}').val(editor.getHtml());
                $('#textarea{$htmlid}').change();
            }
        },
        hooks: {
            'addImageBlobHook': function(blob, callback) {
                var formData = new FormData();
                formData.append('image', blob);
                $.ajax({
                    url: "{$fileserver}",
                    enctype: 'multipart/form-data',
                    data: formData,
                    dataType : 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    type: 'POST',
                    success: function(response){
                        callback(response.url, '');
                        return false;
                    },
                    error: function(e) {
                    }
                });
            }
        },
        exts: ['scrollSync']
      });
});
</script>
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);
        $html  = <<<EOT
        {$value}
EOT;

        return $html;
    }
}
