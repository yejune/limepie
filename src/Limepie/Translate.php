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

        throw new \Limepie\Exception('"' . $property . '" function not found', 999);
    }

    public function count()
    {
        return \count($this->language);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->language[] = $value;
        } else {
            $this->language[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->language[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->language[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->language[$offset];

        return $this->language[$offset] ?? null;
    }

    //iterator_to_array
    public function toArray()
    {
        if (true === \Limepie\is_assoc($this->language)) {
            return $this->language;
        }
        $language = [];

        foreach ($this->language as $index => $property) {
            //index에서 seq로 변경
            $language[] = $property->toArray();
        }

        return $language;
    }

    public function rewind()
    {
        \reset($this->language);
    }

    public function current()
    {
        return \current($this->language);
    }

    public function key()
    {
        return \key($this->language);
    }

    public function next()
    {
        return \next($this->language);
    }

    public function valid()
    {
        $key = \key($this->language);

        return (null !== $key && false !== $key);
    }
}
