<?php

declare(strict_types=1);

namespace Limepie\Form\Generator;

class Store implements \ArrayAccess
{
    // public $value = [];

    public function __construct(public $value = [], public $property = [])
    {
    }

    public function offsetSet($offset, $value) : void
    {
        // readonly, set안함

        // if (\is_null($offset)) {
        //     $this->value[] = $value;
        // } else {
        //     $this->value[$offset] = $value;
        // }
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->value[$offset]);
    }

    public function offsetUnset($offset) : void
    {
        unset($this->value[$offset]);
    }

    public function offsetGet($offset) : mixed
    {
        return isset($this->value[$offset]) ? $this->value[$offset] : null;
    }

    public function __isset($name)
    {
        return isset($this->value[$name]);
    }

    public function __set($key, $value)
    {
        $this->value[$key] = $value;

        return $this;
    }

    public function __get($name)
    {
        if (true === isset($this->value[$name])) {
            return $this->value[$name];
        }

        throw new \Limepie\Exception(\get_called_class() . ': Column "' . $name . '" not found #9', 500);
    }
}
