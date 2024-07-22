<?php declare(strict_types=1);

namespace Limepie;

class HTMLMinifier
{
    private $protectedTags;

    private $removeTagSpaces;

    private $removeComments;

    private $protectedContent = [];

    /**
     * HTMLMinifier 생성자.
     *
     * @param array $protectedTags   보호할 태그 목록
     * @param bool  $removeTagSpaces 태그 주변의 공백 제거 여부
     * @param bool  $removeComments  주석 제거 여부
     */
    public function __construct($protectedTags = ['pre', 'textarea'], $removeTagSpaces = true, $removeComments = true)
    {
        $this->protectedTags   = $protectedTags;
        $this->removeTagSpaces = $removeTagSpaces;
        $this->removeComments  = $removeComments;
    }

    /**
     * HTML 문자열 최소화.
     *
     * @param string $input 입력 HTML 문자열
     *
     * @return string 최소화된 HTML 문자열
     */
    public function minify($input)
    {
        $input = $this->protectPHPBlocks($input);
        $input = $this->processScriptTags($input);
        $input = $this->processStyleTags($input);
        $input = $this->protectTags($input);

        if ($this->removeComments) {
            $input = $this->removeHTMLComments($input);
        }

        $input = $this->removeWhitespace($input);

        if ($this->removeTagSpaces) {
            $input = $this->removeTagSpaces($input);
        }

        $input = $this->restoreProtectedContent($input);

        return $this->restorePHPBlocks($input);
    }

    /**
     * PHP 코드 블록 보호.
     *
     * @param mixed $input
     */
    private function protectPHPBlocks($input)
    {
        return \preg_replace_callback('/<\?php(.*?)\?>/s', function ($matches) {
            return '##PHP_BLOCK' . \base64_encode($matches[0]) . '##';
        }, $input);
    }

    /**
     * Template Literals 보호.
     *
     * @param string $script
     *
     * @return string
     */
    private function protectTemplateLiterals($script)
    {
        return \preg_replace_callback('/`(?:\\\.|[^`\\\])*`/s', function ($matches) {
            return '##TEMPLATE_LITERAL' . \base64_encode($matches[0]) . '##';
        }, $script);
    }

    /**
     * script 태그 처리.
     *
     * @param mixed $input
     */
    private function processScriptTags($input)
    {
        return \preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/s', function ($matches) {
            $scriptTag     = $matches[0];
            $scriptContent = $matches[1];

            if (false !== \strpos($scriptTag, 'src=')) {
                return $scriptTag;
            }

            if ($this->removeComments) {
                $scriptContent = $this->processJavaScript($scriptContent);
            }

            $scriptContent = \preg_replace('/\s+/', ' ', $scriptContent);

            return \str_replace($matches[1], \trim($scriptContent), $scriptTag);
        }, $input);
    }

    /**
     * JavaScript 코드 처리.
     *
     * @param mixed $script
     */
    private function processJavaScript($script)
    {
        $script = $this->protectTemplateLiterals($script);

        // 여러 줄 주석 제거
        $script = \preg_replace('/\/\*[\s\S]*?\*\//', '', $script);

        $lines          = \explode("\n", $script);
        $processedLines = [];

        foreach ($lines as $line) {
            $processedLine = $this->processJavaScriptLine($line);

            if (!empty($processedLine)) {
                $processedLines[] = $processedLine;
            }
        }

        $script = \implode("\n", $processedLines);

        return $this->restoreTemplateLiterals($script);
    }

    /**
     * JavaScript 한 줄 처리.
     *
     * @param mixed $line
     */
    private function processJavaScriptLine($line)
    {
        $result     = '';
        $inString   = false;
        $stringChar = '';
        $inRegex    = false;
        $length     = \strlen($line);

        for ($i = 0; $i < $length; ++$i) {
            $char     = $line[$i];
            $nextChar = ($i < $length - 1) ? $line[$i + 1] : '';
            $prevChar = ($i > 0) ? $line[$i - 1] : '';

            if (!$inString && !$inRegex) {
                if ('/' === $char && '/' === $nextChar && '\\' !== $prevChar) {
                    // 한 줄 주석 시작, 여기서 처리 중단
                    break;
                }

                if (('"' === $char || "'" === $char) && '\\' !== $prevChar) {
                    $inString   = true;
                    $stringChar = $char;
                    $result .= $char;
                } elseif ('/' === $char && '\\' !== $prevChar) {
                    // 정규식 시작 가능성 체크 (이 부분 수정)
                    $j = $i - 1;

                    while ($j >= 0 && \ctype_space($line[$j])) {
                        --$j;
                    }

                    if ($j < 0 || \preg_match('/[\(\[=,:!&|?+\-~*\/%<>^]$/', \substr($line, 0, $j + 1))) {
                        $inRegex = true;
                    }
                    $result .= $char;
                } else {
                    $result .= $char;
                }
            } elseif ($inString) {
                $result .= $char;

                if ($char === $stringChar && '\\' !== $prevChar) {
                    $inString = false;
                }
            } elseif ($inRegex) {
                $result .= $char;

                if ('/' === $char && '\\' !== $prevChar) {
                    $inRegex = false;

                    // 정규식 플래그 추가
                    while ($i + 1 < $length && \preg_match('/[gimsuy]/', $line[$i + 1])) {
                        $result .= $line[++$i];
                    }

                    // 정규식 종료 후 추가 체크 (이 부분 추가)
                    $nextNonSpaceChar = $this->getNextNonSpaceChar($line, $i + 1);

                    if ($nextNonSpaceChar && !\in_array($nextNonSpaceChar, [';', ')', ']', '}', ','])) {
                        $inRegex = true; // 정규식이 아닐 수 있으므로 다시 정규식 모드로 진입
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 다음 공백이 아닌 문자 가져오기.
     *
     * @param string $line
     * @param int    $startIndex
     *
     * @return null|string
     */
    private function getNextNonSpaceChar($line, $startIndex)
    {
        $length = \strlen($line);

        for ($i = $startIndex; $i < $length; ++$i) {
            if (!\ctype_space($line[$i])) {
                return $line[$i];
            }
        }

        return null;
    }

    /**
     * style 태그 처리.
     *
     * @param mixed $input
     */
    private function processStyleTags($input)
    {
        return \preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/s', function ($matches) {
            $styleTag     = $matches[0];
            $styleContent = $matches[1];

            if ($this->removeComments) {
                $styleContent = \preg_replace('/\/\*.*?\*\//s', '', $styleContent);
            }

            $styleContent = \preg_replace('/\s+/', ' ', $styleContent);

            return \str_replace($matches[1], $styleContent, $styleTag);
        }, $input);
    }

    /**
     * 지정된 태그 보호.
     *
     * @param mixed $input
     */
    private function protectTags($input)
    {
        foreach ($this->protectedTags as $tag) {
            $pattern = "/<{$tag}\\b[^>]*>(.*?)<\\/{$tag}>/s";
            $input   = \preg_replace_callback($pattern, function ($matches) use ($tag) {
                $key                          = '##' . \strtoupper($tag) . '_BLOCK' . \count($this->protectedContent) . '##';
                $this->protectedContent[$key] = $matches[0];

                return $key;
            }, $input);
        }

        return $input;
    }

    /**
     * HTML 주석 제거.
     *
     * @param mixed $input
     */
    private function removeHTMLComments($input)
    {
        return \preg_replace('/<!--.*?-->/s', '', $input);
    }

    /**
     * 불필요한 공백 제거.
     *
     * @param mixed $input
     */
    private function removeWhitespace($input)
    {
        return \preg_replace('/\s+/', ' ', $input);
    }

    /**
     * 태그 주변의 공백 제거.
     *
     * @param mixed $input
     */
    private function removeTagSpaces($input)
    {
        $input = \preg_replace('/>\s+/', '>', $input);
        $input = \preg_replace('/\s+</', '<', $input);

        return \preg_replace('/>\s+</', '><', $input);
    }

    /**
     * 보호된 내용 복원.
     *
     * @param mixed $input
     */
    private function restoreProtectedContent($input)
    {
        foreach ($this->protectedContent as $key => $value) {
            $input = \str_replace($key, $value, $input);
        }

        return $input;
    }

    /**
     * PHP 블록 복원.
     *
     * @param mixed $input
     */
    private function restorePHPBlocks($input)
    {
        return \preg_replace_callback('/##PHP_BLOCK(.+?)##/', function ($matches) {
            return \base64_decode($matches[1]);
        }, $input);
    }

    /**
     * Template Literals 복원.
     *
     * @param string $script
     *
     * @return string
     */
    private function restoreTemplateLiterals($script)
    {
        return \preg_replace_callback('/##TEMPLATE_LITERAL(.+?)##/', function ($matches) {
            return \base64_decode($matches[1]);
        }, $script);
    }
}
