<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

use Limepie\Form\Generation\Fields;

class File extends Fields
{
    public static function write($key, $property, $data, $ruleName)
    {
        if (true === \Limepie\arr\is_file_array($data, false)) {
            $value  = \htmlspecialchars((string) $data['name']);
            $accept = $property['rules']['accept'] ?? '';
            $button = '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="{$value}" readonly="readonly" />
            <input type="text" class='form-control-file form-control-filetext' name="{$key}" value="{$value}" accept="{$accept}" />
EOT;
            $button = <<<'EOT'
            <button class="btn btn-filesearch-text" type="button"><span class="fas fa-search"></span></button>
EOT;
        } else {
            $value  = '';
            $accept = $property['rules']['accept'] ?? '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="" readonly="readonly" />
            <input type="file" class='form-control-file' name="{$key}" data-rule-name="{$ruleName}" value="{$value}" accept="{$accept}" />
EOT;
            $button = <<<'EOT'
            <button class="btn btn-filesearch" type="button"><span class="fas fa-search"></span></button>
EOT;
        }

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
