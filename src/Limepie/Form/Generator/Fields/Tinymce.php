<?php declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class Tinymce extends Fields
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
        $rows    = $property['rows']    ?? 3;
        $height  = $property['height']  ?? 300;

        $upload = $property['fileserver'] ?? 'upload';
        $id     = \uniqid();
        $type   = $property['type'] ?? 'tinymce';

        return <<<EOT
        <textarea id="tinymce{$id}" class="valid-target form-control tinymcearea" name="{$key}" data-type="{$type}"  data-height="{$height}" data-upload-server="{$upload}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  data-default="{$default}" rows="{$rows}">{$value}</textarea>
        <script class="clone-element" nonce="{$_SESSION['nonce']}">
        $(function() {
            var id = '#tinymce{$id}';
            var height = {$height};
            var upload = '{$upload}';

            editor_tinymce(id, height, upload);
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
