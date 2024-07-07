<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Summernote5 extends Fields
{
    public static function write($key, $property, $value, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $upload = $property['fileserver'] ?? 'upload';
        $id     = \uniqid();

        return <<<EOT
        <textarea id="summernote{$id}" class="valid-target form-control summernote" name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}" rows="{$rows}">{$value}</textarea>
<script nonce="{$_SESSION['nonce']}">
$(function() {

    var summernoteElement = $('#summernote{$id}');
    summernoteElement.summernote({
        //dialogsInBody: true,
        minHeight: 300,
        callbacks: {
            onInit: function(contents, editable) {
                var summernote = $(this);
                if(summernote.summernote('isEmpty')) {
                    summernote.val('');
                }

                summernote.closest("form").data("validator").checkByElements(summernoteElement);
                $("button[data-toggle='dropdown']").each(function (index) {
                    $(this).removeAttr("data-toggle").attr("data-bs-toggle", "dropdown");
                });

                $('button[data-event="showImageDialog"]').attr('data-bs-toggle', 'image').removeAttr('data-event');
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

                summernote.closest("form").data("validator").checkByElements(summernoteElement);
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
