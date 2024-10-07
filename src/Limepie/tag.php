<?php

declare(strict_types=1);

namespace Limepie\tag;

// 구분 문자열을 특정테그로 바꿈
function replace($separator, $tag, $string, $class = '')
{
    return \str_replace($separator, '</' . $tag . '><' . $tag . ($class ? 'class="' . $class . '"' : '') . '>', $string);
}

