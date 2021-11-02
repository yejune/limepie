<?php declare(strict_types=1);

namespace Limepie\RecursiveIterator;

class AdjacencyList extends \RecursiveArrayIterator
{
    private $adjacencyList;

    private $children;

    public function __construct(array $adjacencyList, array $array = null, $flags = 0)
    {
        $this->adjacencyList = $adjacencyList;

        $array = null !== $array
            ? $array
            : \array_filter($adjacencyList, function ($node) {
                return null === $node['parent'] || 0 === $node['parent'];
            });

        parent::__construct($array, $flags);
    }

    public function hasChildren() : bool
    {
        $children = \array_filter($this->adjacencyList, function ($node) {
            return $this->current()['seq'] === $node['parent'];
        });

        if (!empty($children)) {
            $this->children = $children;

            return true;
        }

        return false;
    }

    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return new static($this->adjacencyList, $this->children);
    }
}
