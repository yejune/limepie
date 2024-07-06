<?php
function removeLineBreaksAndSpaces($input, $protectedTags = ['pre', 'textarea'], $removeTagSpaces = true, $removeComments = true)
{
    // PHP 코드 블록 보호
    $input = \preg_replace_callback('/<\?php(.*?)\?>/s', function ($matches) {
        return '##PHP_BLOCK' . \base64_encode($matches[0]) . '##';
    }, $input);

    // 선택된 태그 보호
    $protectedContent = [];

    foreach ($protectedTags as $tag) {
        $pattern = "/<{$tag}\\b[^>]*>(.*?)<\\/{$tag}>/s";
        $input   = \preg_replace_callback($pattern, function ($matches) use (&$protectedContent, $tag) {
            $key                    = '##' . \strtoupper($tag) . '_BLOCK' . \count($protectedContent) . '##';
            $protectedContent[$key] = $matches[0];

            return $key;
        }, $input);
    }

    // 주석 제거 (선택적)
    if ($removeComments) {
        // HTML 주석 제거
        $input = \preg_replace('/<!--.*?-->/s', '', $input);
        // CSS/JS 주석 제거
        $input = \preg_replace('/\/\*.*?\*\//s', '', $input);
    }

    // 줄바꿈과 연속된 공백 제거
    $input = \preg_replace('/\s+/', ' ', $input);

    // 태그 주변의 공백 제거 (선택적)
    if ($removeTagSpaces) {
        $input = \preg_replace('/>\s+/', '>', $input);  // '>' 다음의 공백 제거
        $input = \preg_replace('/\s+</', '<', $input);  // '<' 전의 공백 제거
        $input = \preg_replace('/>\s+</', '><', $input);  // 태그 사이의 공백 제거
    }

    // 보호된 내용 복원
    foreach ($protectedContent as $key => $value) {
        $input = \str_replace($key, $value, $input);
    }

    // PHP 코드 블록 복원
    return \preg_replace_callback('/##PHP_BLOCK(.+?)##/', function ($matches) {
        return \base64_decode($matches[1]);
    }, $input);
}
function removeSpace($source, $tpl)
{
    return \removeLineBreaksAndSpaces($source);
}
