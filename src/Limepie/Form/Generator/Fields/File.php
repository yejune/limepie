<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\Form\Generator\Fields;

class File extends Fields
{
    public static function write($key, $property, $data, $ruleName, $propertyName)
    {
        if (true === isset($property['rule_name'])) {
            $ruleName = $property['rule_name'];
        }

        if (true === \Limepie\arr\is_file_array($data, false)) {
            $value  = \htmlspecialchars((string) $data['name']);
            $accept = $property['rules']['accept'] ?? '';
            $button = '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="{$value}" readonly="readonly" />
            EOT;
            // $html .= <<<EOT
            //     <input type="text" class='form-control-file form-control-filetext' name="{$key}" value="{$value}" accept="{$accept}" />
            // EOT;

            foreach ($data as $key1 => $value1) {
                if (\in_array($key1, ['name', 'type', 'size', 'tmp_name', 'error', 'full_path', 'file_name_alias_seq', 'url'])) {
                    if ('name' === $key1) {
                        $html .= <<<EOT
                        <input type="text" class='valid-target form-control-file form-control-filetext' name="{$key}[{$key1}]" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value1}" accept="{$accept}" />
                        EOT;
                    } else {
                        if ('tmp_name' === $key1) {
                        } else {
                            $html .= <<<EOT
                            <input type="hidden" class="clone-element" name="{$key}[{$key1}]" value="{$value1}" />
                            EOT;
                        }
                    }
                }
            }
            $button = <<<'EOT'
            <button class="btn btn-search btn-file-search-text" type="button">&nbsp;</button>
            EOT;
        } else {
            $value  = '';
            $accept = $property['rules']['accept'] ?? '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="" readonly="readonly" /><input type="file" class='valid-target form-control-file' name="{$key}" data-name="{$propertyName}" data-rule-name="{$ruleName}"  value="{$value}" accept="{$accept}" />
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

        if (true === \Limepie\arr\is_file_array($data, false)) {
            $value = \str_replace('', '', (string) $data['path']);
            $html  = <<<EOT
                <img src="{$value}" />
            EOT;
        }

        return $html;
    }
}
