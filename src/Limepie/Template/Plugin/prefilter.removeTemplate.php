<?php declare(strict_types=1);

function removeTemplate($html)
{
    $pattern = '/<([\w-]+)\s+([^>]*?)limepie-template=(["\'`])((?:\\\.|(?!\3).)*?)\3(.*?)(\s*\/?>)/s';

    return \preg_replace_callback($pattern, function ($matches) {
        $tag        = $matches[1];
        $beforeAttr = $matches[2];
        $content    = $matches[4];
        $afterAttr  = $matches[5];
        $closing    = $matches[6];

        // 이스케이프된 따옴표 처리
        $content = \preg_replace('/\\\([\'"])/', '$1', $content);

        return "<{$tag} {$beforeAttr}{$content}{$afterAttr}{$closing}";
    }, $html);
}
