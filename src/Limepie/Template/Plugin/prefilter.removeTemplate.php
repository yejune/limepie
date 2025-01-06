<?php declare(strict_types=1);

function removeTemplate($html)
{
    $pattern = '/\s+limepie-template=(["\'`])\s*([\s\S]*?)\s*\1/';

    return \preg_replace_callback($pattern, function ($matches) {
        $content = $matches[2];

        // 각 줄의 불필요한 들여쓰기 제거
        $lines   = \explode("\n", $content);
        $lines   = \array_map('trim', $lines);
        $content = \implode(' ', \array_filter($lines));

        return ' ' . \trim($content);
    }, $html);
}
