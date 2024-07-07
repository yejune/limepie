<?php
declare(strict_types=1);
use Limepie\HTMLMinifier;

function removeSpace($source, $tpl)
{
    $minifier = new HTMLMinifier(['pre', 'textarea'], true, true);

    return $minifier->minify($source);
}
