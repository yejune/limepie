<?php
declare(strict_types=1);

use Masterminds\HTML5;

function removeTagWhitespace($html, $tpl)
{
    $html5 = new HTML5(['disable_html_ns' => true]);

    // Create a new DOMDocument
    $document = new DOMDocument();

    // Create a wrapper to ensure proper parsing
    $wrappedHtml = '<html><body>' . $html . '</body></html>';

    // Load the complete HTML
    $document = $html5->loadHTML($wrappedHtml);

    // Create xpath
    $xpath = new DOMXPath($document);

    foreach ($xpath->query('//text()') as $textNode) {
        $text        = $textNode->nodeValue;
        $trimmedText = \trim($text);

        if ('' === $trimmedText) {
            $textNode->parentNode->removeChild($textNode);
        } else {
            $textNode->nodeValue = $trimmedText;
        }
    }

    // Get only the content inside body
    $bodyContent = '';
    $bodyNodes   = $xpath->query('/html/body/node()');

    foreach ($bodyNodes as $node) {
        $bodyContent .= $html5->saveHTML($node);
    }

    return $bodyContent;
}

// function removeTagWhitespace($html, $tpl)
// {
//     return \Limepie\remove_tag_whitespace($html);
// }
