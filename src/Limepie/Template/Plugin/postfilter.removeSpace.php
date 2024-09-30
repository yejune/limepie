<?php
declare(strict_types=1);
// use WyriHaximus\HtmlCompress\Factory;

function removeSpace($content, $tpl)
{
    // 공백을 유지해야 하는 패턴 목록
    $preservePatterns = [
        'tag' => ['textarea', 'pre', 'code'],
        'php' => '/<\?php.*?\?>/s', // PHP 코드 블록 패턴
    ];

    // 내용 저장을 위한 배열
    $placeholders = [];

    // 1. PHP 코드 블록을 임시로 다른 문자열로 대체
    $content = \preg_replace_callback($preservePatterns['php'], function ($matches) use (&$placeholders) {
        static $index = 0;
        $placeholder  = "##PHP_PLACEHOLDER{$index}##";
        ++$index;
        $placeholders[$placeholder] = $matches[0];

        return $placeholder;
    }, $content);

    // 2. 공백을 유지해야 하는 태그의 내용을 임시로 다른 문자열로 대체
    foreach ($preservePatterns['tag'] as $tag) {
        $escapedTag = \preg_quote($tag, '/');
        $content    = \preg_replace_callback("/(<{$escapedTag}\\b[^>]*>)(.*?)(<\\/{$escapedTag}>)/is", function ($matches) use (&$placeholders) {
            static $index = 0;
            $placeholder  = "##TAG_PLACEHOLDER{$index}##";
            ++$index;
            $placeholders[$placeholder] = $matches[2];

            return $matches[1] . $placeholder . $matches[3];
        }, $content);
    }

    // 3. <script> 태그 처리
    $content = \preg_replace_callback('/(<script\b[^>]*>)(.*?)(<\/script>)/is', function ($matches) {
        $openTag       = $matches[1];
        $scriptContent = $matches[2];
        $closeTag      = $matches[3];

        // 문자열 리터럴을 임시로 다른 문자열로 대체
        $strings       = [];
        $scriptContent = \preg_replace_callback('/([\'"])(?:\\\.|[^\\\])*?\1/s', function ($matches) use (&$strings) {
            static $index = 0;
            $placeholder  = "##STRING_PLACEHOLDER{$index}##";
            ++$index;
            $strings[$placeholder] = $matches[0];

            return $placeholder;
        }, $scriptContent);

        // 정규식 리터럴을 임시로 다른 문자열로 대체
        $regexes       = [];
        $scriptContent = \preg_replace_callback('~
            (?:
                (?<=[\(\[{\s=,:;!&|?]|^)
                /
                (?:\\\.|[^/])+
                /
                [gimsuy]*
            )
        ~mx', function ($matches) use (&$regexes) {
            static $index = 0;
            $placeholder  = "##REGEX_PLACEHOLDER{$index}##";
            ++$index;
            $regexes[$placeholder] = $matches[0];

            return $placeholder;
        }, $scriptContent);

        // 주석 제거
        // 단일 행 주석
        $scriptContent = \preg_replace('/\/\/.*?(?=[\r\n]|$)/', '', $scriptContent);
        // 다중 행 주석
        $scriptContent = \preg_replace('/\/\*.*?\*\//s', '', $scriptContent);

        // 줄바꿈 및 탭 제거
        $scriptContent = \str_replace(["\r", "\n", "\t"], '', $scriptContent);
        // 연속된 공백을 하나로
        $scriptContent = \preg_replace('/\s+/', ' ', $scriptContent);

        // 정규식 리터럴 복원
        foreach ($regexes as $placeholder => $originalRegex) {
            $scriptContent = \str_replace($placeholder, $originalRegex, $scriptContent);
        }

        // 문자열 리터럴 복원
        foreach ($strings as $placeholder => $originalString) {
            $scriptContent = \str_replace($placeholder, $originalString, $scriptContent);
        }

        return $openTag . $scriptContent . $closeTag;
    }, $content);

    // 4. <style> 태그 처리
    $content = \preg_replace_callback('/(<style\b[^>]*>)(.*?)(<\/style>)/is', function ($matches) {
        $openTag      = $matches[1];
        $styleContent = $matches[2];
        $closeTag     = $matches[3];

        // CSS 주석 제거
        $styleContent = \preg_replace('/\/\*.*?\*\//s', '', $styleContent);

        // 줄바꿈 및 탭 제거
        $styleContent = \str_replace(["\r", "\n", "\t"], '', $styleContent);
        // 연속된 공백을 하나로
        $styleContent = \preg_replace('/\s+/', ' ', $styleContent);

        return $openTag . $styleContent . $closeTag;
    }, $content);

    // 5. HTML 주석 제거
    $content = \preg_replace('/<!--.*?-->/s', '', $content);

    // 6. 줄바꿈 및 탭 제거 (HTML 부분)
    $content = \str_replace(["\r", "\n", "\t"], '', $content);

    // 연속된 공백을 하나로
    $content = \preg_replace('/\s+/', ' ', $content);

    // 추가: '>'와 '<' 사이의 공백 제거
    $content = \preg_replace('/>\s+</', '><', $content);

    // 7. 이전에 저장한 태그 내용 및 PHP 코드 블록 복원
    foreach ($placeholders as $placeholder => $originalContent) {
        $content = \str_replace($placeholder, $originalContent, $content);
    }

    return \trim($content);
}
