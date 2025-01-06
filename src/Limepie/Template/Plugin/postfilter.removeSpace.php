<?php

declare(strict_types=1);
// $template->postfilter   = 'removeSpace & ' . (Di::getServiceSpecModel(null)?->getSpec(null)?->getIsTagWhitespaceRemove(null) ?? 0);
// 이 옵션은 제거
function removeSpace($content, $tpl) // , $isTagWhitespaceRemove = 0)
{
    // 공백을 유지해야 하는 패턴 목록
    $preservePatterns = [
        'tag' => ['textarea', 'pre', 'code'],
        'php' => '/<\?php.*?\?>/s', // PHP 코드 블록 패턴
    ];

    // 내용 저장을 위한 배열
    $placeholders = [];
    $step         = 0;

    // 다중 행 주석 처리 (/* space-start */ ... /** space-end */)
    $content = \preg_replace_callback('/\/\*\s*space-start\s*\*\/(.*?)\/\*\s*space-end\s*\*\//s', function ($matches) use (&$placeholders, &$step) {
        $placeholder                = "###COMMENT_PLACEHOLDER{$step}###";
        $placeholders[$placeholder] = $matches[1]; // 주석 사이의 내용을 그대로 보존 (줄바꿈 포함)
        ++$step;

        return $placeholder;
    }, $content);

    // 단일 행 주석 처리 (// space-start ... // space-end)
    $content = \preg_replace_callback('/\/\/\s*space-start(.*?)\/\/\s*space-end/s', function ($matches) use (&$placeholders, &$step) {
        $placeholder                = "###COMMENT_PLACEHOLDER{$step}###";
        $placeholders[$placeholder] = $matches[1]; // 주석 사이의 내용을 그대로 보존 (줄바꿈 포함)
        ++$step;

        return $placeholder;
    }, $content);

    // HTML 주석 처리 (<!-- space-start --> ... <!--space-end-->)
    $content = \preg_replace_callback('/<!--\s*space-start\s*-->(.*?)<!--\s*space-end\s*-->/s', function ($matches) use (&$placeholders, &$step) {
        $placeholder                = "###COMMENT_PLACEHOLDER{$step}###";
        $placeholders[$placeholder] = $matches[1]; // 주석 사이의 내용을 그대로 보존 (줄바꿈 포함)
        ++$step;

        return $placeholder;
    }, $content);

    // 1. PHP 코드 블록을 임시로 다른 문자열로 대체
    $content = \preg_replace_callback($preservePatterns['php'], function ($matches) use (&$placeholders, &$step) {
        $placeholder = "###PHP_PLACEHOLDER{$step}###";
        ++$step;
        $placeholders[$placeholder] = $matches[0];

        return $placeholder;
    }, $content);

    // 2. 공백을 유지해야 하는 태그의 내용을 임시로 다른 문자열로 대체
    foreach ($preservePatterns['tag'] as $tag) {
        $escapedTag = \preg_quote($tag, '/');
        $content    = \preg_replace_callback("/(<\\s*{$escapedTag}\\s*\\b[^>]*>)(.*?)(<\\s*\\/\\s*{$escapedTag}\\s*>)/is", function ($matches) use (&$placeholders, &$step) {
            $placeholder = "###TAG_PLACEHOLDER{$step}###";
            ++$step;
            $placeholders[$placeholder] = $matches[2];

            return $matches[1] . $placeholder . $matches[3];
        }, $content);
    }

    // 3. <script> 태그 처리
    $content = \preg_replace_callback('/(<\s*script\s*\b[^>]*>)(.*?)(<\s*\/\s*script\s*>)/is', function ($matches) use (&$placeholders, &$step) {
        $openTag       = $matches[1];
        $scriptContent = $matches[2];
        $closeTag      = $matches[3];

        // 백틱(템플릿 문자열)을 처리
        $scriptContent = \preg_replace_callback('/`((?:\\\.|[^\\\]|[\r\n])*?)`/', function ($matches) use (&$placeholders, &$step) {
            $placeholder = "###TEMPLATE_PLACEHOLDER{$step}###";
            ++$step;
            $placeholders[$placeholder] = $matches[0];

            return $placeholder;
        }, $scriptContent);

        // 일반 문자열(싱글/더블 쿼트)을 처리
        $scriptContent = \preg_replace_callback('/([\'"])((?:[^\1\\\\\r\n]|\\\.)*)\1/', function ($matches) use (&$placeholders, &$step) {
            $fullString = $matches[0];
            $quote      = $matches[1];
            $content    = $matches[2];

            // 현재 쿼트 타입의 수만 체크 (다른 타입은 무시)
            // 문자열 내 이스케이프되지 않은 따옴표를 정확하게 찾음
            $backslash  = '\\\\';  // 정규식에서 단일 백슬래시를 나타내기 위한 패턴
            $quoteCount = \preg_match_all(
                '/' . \preg_quote('(?<!' . $backslash . ')', '/') . \preg_quote($quote, '/') . '/',
                $fullString
            );

            if (0 !== $quoteCount % 2) {
                return $fullString;
            }

            $placeholder = "###STRING_PLACEHOLDER{$step}###";
            ++$step;
            $placeholders[$placeholder] = $fullString;

            return $placeholder;
        }, $scriptContent);

        // 정규식 리터럴을 임시로 다른 문자열로 대체
        $scriptContent = \preg_replace_callback('~
            (?<![\d\w])                    # 숫자나 문자가 앞에 오지 않음
            (?:
                (?<=[\(\[{\s=,:;!&|?]|^)   # 정규식이 올 수 있는 문맥 확인
                /                          # 시작 구분자
                (?:
                    [^\/\n]|               # 슬래시나 줄바꿈이 아닌 문자
                    \.                     # 또는 이스케이프된 문자
                )+
                /                          # 종료 구분자
                [gimsuy]*                  # 정규식 플래그
            )
        ~mx', function ($matches) use (&$placeholders, &$step) {
            $placeholder                = "###REGEX_PLACEHOLDER{$step}###";
            $placeholders[$placeholder] = $matches[0];
            ++$step;

            return $placeholder;
        }, $scriptContent);

        // 주석 제거
        // 단일 행 주석
        $scriptContent = \preg_replace('/\/\/.*?(?=[\r\n]|$)/', '', $scriptContent);
        // // // 다중 행 주석
        $scriptContent = \preg_replace('/\/\*.*?\*\//s', '', $scriptContent);
        // $scriptContent = \preg_replace('/\/\/.*/', '', $scriptContent);  // 한 줄 주석
        // $scriptContent = \preg_replace('/\/\*.*?\*\//s', '', $scriptContent);  // 여러 줄 주석

        // 줄바꿈 및 탭 제거
        $scriptContent = \str_replace(["\r", "\n", "\t"], '', $scriptContent);
        // 연속된 공백을 하나로
        $scriptContent = \preg_replace('/\s+/', ' ', $scriptContent);
        $scriptContent = \preg_replace('/;+/', ';', $scriptContent);

        return $openTag . $scriptContent . $closeTag;
    }, $content);

    // 4. <style> 태그 처리
    $content = \preg_replace_callback('/(<\s*style\s*\b[^>]*>)(.*?)(<\s*\/\s*style\s*>)/is', function ($matches) {
        $openTag      = $matches[1];
        $styleContent = $matches[2];
        $closeTag     = $matches[3];

        // CSS 주석 제거
        $styleContent = \preg_replace('/\/\*.*?\*\//s', '', $styleContent);

        // 줄바꿈 및 탭 제거
        $styleContent = \str_replace(["\r", "\n", "\t"], '', $styleContent);
        // 연속된 공백을 하나로
        $styleContent = \preg_replace('/\s+/', ' ', $styleContent);
        $styleContent = \preg_replace('/;+/', ';', $styleContent);

        return $openTag . $styleContent . $closeTag;
    }, $content);

    // 5. HTML 주석 제거
    $content = \preg_replace('/<!--.*?-->/s', '', $content);

    // 6. 줄바꿈 및 탭을 공백으로
    $content = \str_replace(["\r", "\n", "\t"], ' ', $content);

    // 순수 테그 주변 공백 제거, php는 치환되지 않은 상태
    // -> 주변 공백을 제거하면 의도한 공백들이 문제될수 있어서 제거

    // 7. 연속된 공백을 하나로
    $content = \preg_replace('/\s+/', ' ', $content);

    // 8. 키 유지한체 역순으로 복원
    $placeholders = \array_reverse($placeholders, true);

    // 9. 이전에 저장한 태그 내용 및 PHP 코드 블록 복원
    foreach ($placeholders as $placeholder => $originalContent) {
        $content = \str_replace($placeholder, $originalContent, $content);
    }

    // 더 이상 고민 말것. 깔끔하게 처리할수 없음.
    // if (2 == $isTagWhitespaceRemove) {
    //     // 1) `> ... <` 사이 공백 제거 (단, 바로 옆에 ?가 있으면 제외)
    //     $content = \preg_replace('/(?<!\?)>\s+<(?!\?)/', '><', $content);

    //     // 2) `> ...` 앞에 공백 제거 (단, 바로 옆에 ?가 있으면 제외)
    //     $content = \preg_replace('/(?<!\?)>\s+/', '>', $content);

    //     // 3) `... >` 뒤에 공백 제거 (단, 바로 옆에 ?가 있으면 제외)
    //     $content = \preg_replace('/\s+>(?!\?)/', '>', $content);

    //     // 4) `< ...` 앞에 공백 제거 (단, 바로 옆에 ?가 있으면 제외)
    //     $content = \preg_replace('/\s+<(?!\?)/', '<', $content);
    // }

    return \trim($content);
}
