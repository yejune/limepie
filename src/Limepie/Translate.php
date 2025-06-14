<?php declare(strict_types=1);

namespace Limepie;

class Translate implements \Iterator, \ArrayAccess, \Countable
{
    private $language;

    public function __construct($language)
    {
        $this->language = $language;
    }

    public function __get($property)
    {
        if (true === isset($this->language[$property])) {
            return $this->language[$property];
        }
    }

    public function __set($property, $value)
    {
        $this->language[$property] = $value;

        return $this;
    }

    public function __call($property, $arguments)
    {
        if (0 === \strpos($property, 'set')) {
            $fieldName                  = \Limepie\decamelize(\substr($property, 3));
            $this->language[$fieldName] = $arguments[0];

            return $this;
        }

        if (0 === \strpos($property, 'get')) {
            $fieldName = \Limepie\decamelize(\substr($property, 3));

            return $this->language[$fieldName] ?? null;
        }

        throw new Exception('"' . $property . '" function not found', 999);
    }

    public function count() : int
    {
        return \count($this->language);
    }

    public function offsetSet($offset, $value) : void
    {
        if (null === $offset) {
            $this->language[] = $value;
        } else {
            $this->language[$offset] = $value;
        }
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->language[$offset]);
    }

    public function offsetUnset($offset) : void
    {
        unset($this->language[$offset]);
    }

    public function offsetGet($offset) : mixed
    {
        return $this->language[$offset];

        return $this->language[$offset] ?? null;
    }

    // iterator_to_array
    public function toArray()
    {
        if (true === arr::is_assoc($this->language)) {
            return $this->language;
        }
        $language = [];

        foreach ($this->language as $index => $property) {
            // index에서 seq로 변경
            $language[] = $property->toArray();
        }

        return $language;
    }

    public function rewind() : void
    {
        \reset($this->language);
    }

    public function current() : mixed
    {
        return \current($this->language);
    }

    public function key() : mixed
    {
        return \key($this->language);
    }

    public function next() : void
    {
        \next($this->language);
    }

    public function valid() : bool
    {
        $key = \key($this->language);

        return null !== $key && false !== $key;
    }
}
