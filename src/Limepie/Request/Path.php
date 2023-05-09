<?php declare(strict_types=1);

namespace Limepie\Request;

class Path
{
    public $pathString = '';

    public $queryString = '';

    public $segments = [];

    public $parameters = [];

    public function __construct($pathString, $rewriteQueryString = '')
    {
        if (false !== \strpos($pathString, '?')) {
            [$pathString, $this->queryString] = \explode('?', $pathString);
        }
        $this->pathString = \trim($pathString, '/');

        if ($rewriteQueryString) {
            $this->queryString = $rewriteQueryString;
        }

        if ($this->pathString) {
            $this->segments = \explode('/', $this->pathString);

            $this->parameters = [];

            for ($i = 0, $j = \count($this->segments); $i < $j; $i += 2) {
                $this->parameters[$this->segments[$i]] = $this->segments[$i + 1] ?? '';
            }
        }
    }

    public function slice(?int $step = null, $length = null)
    {
        if (null !== $step) {
            $segments = \array_slice($this->segments, $step, $length);

            if ($segments) {
                return '/' . \implode('/', $segments);
            }

            return '';
        }

        return $this->pathString;
    }

    public function getQueryString($append = '?')
    {
        if ($this->queryString) {
            return $append . \ltrim($this->queryString, $append);
        }

        return '';
    }

    public function getSegments() : array
    {
        return $this->segments;
    }

    public function getSegment($index)
    {
        return $this->segments[$index] ?? '';
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key] ?? '';
    }

    public function getPath($abs = null)
    {
        if ($abs) {
            // Count the number of ".." in $abs
            $upCount = \substr_count($abs, '..');

            // Split $path by "/"
            $directories = \explode('/', $this->pathString);

            // Remove directories from the end of $path
            for ($i = 0; $i < $upCount; ++$i) {
                \array_pop($directories);
            }

            // Rejoin the remaining directories into a path string
            return '/' . \implode('/', $directories);
        }

        return '/' . $this->pathString;
    }
}
