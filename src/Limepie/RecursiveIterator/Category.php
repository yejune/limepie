<?php declare(strict_types=1);

namespace Limepie\RecursiveIterator;

class Category extends \RecursiveArrayIterator
{
    private $adjacencyList;

    private $children;

    public function __construct(array $adjacencyList, array $array = null, $flags = 0)
    {
        $this->adjacencyList = $adjacencyList;

        $array = null !== $array
            ? $array
            : \array_filter($adjacencyList, function($node) {
                return null === $node['parent_seq'] || 0 === $node['parent_seq'];
            });

        parent::__construct($array, $flags);
    }

    public function hasChildren()
    {
        $children = \array_filter($this->adjacencyList, function($node) {
            return $this->current()['current_seq'] === $node['parent_seq'];
        });

        if (!empty($children)) {
            $this->children = $children;

            return true;
        }

        return false;
    }

    public function getChildren()
    {
        return new static($this->adjacencyList, $this->children);
    }
}
