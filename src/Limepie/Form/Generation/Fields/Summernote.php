<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Summernote extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value, $ruleName)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $upload = $property['upload'] ?? 'upload';
        $id     = \uniqid();

        return <<<EOT
        <script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.15.0/umd/popper.min.js"   crossorigin="anonymous"></script>
        <link href="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote-bs4.css" rel="stylesheet">
        <script src="//cdnjs.cloudflare.com/ajax/libs/summernote/0.8.12/summernote-bs4.js"></script>
        <textarea id="summernote{$id}" class="form-control summernote" name="{$key}" data-rule-name="{$ruleName}" data-default="{$default}" rows="{$rows}">{$value}</textarea>
<script>
$(function() {

    $('#summernote{$id}').summernote({
        //dialogsInBody: true,
        callbacks: {
            onInit: function(contents, editable) {
                var summernote = $(this);
                if(summernote.summernote('isEmpty')) {
                    summernote.val('');
                }

                let form = summernote.closest("form")[0];
                var validator = $.data( form, "validator" );
                validator.elementValid(this);
            },
            onChange: function(contents, editable) {
                var summernote = $(this);
                if(summernote.summernote('isEmpty')) {
                    summernote.val('');
                } else {
                    if(!this.value) {
                        this.value = summernote.summernote('code');
                    }
                }

                let form = summernote.closest("form")[0];
                var validator = $.data( form, "validator" );
                validator.elementValid(this);
            },
            onImageUpload: function(files) {
                var summernote = $(this);
                for(let i=0; i < files.length; i++) {
                    $.ajaxUpload(summernote, '{$upload}', files[i]);
                }

                $("input[name=files]").val('');
            }
        }
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
