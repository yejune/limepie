<?php declare(strict_types=1);

namespace Limepie;

class ArrayObject implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable, \Serializable
{
    public $attributes = [];

    public function __construct($array)
    {
        // 키를 object형태로 다루려면 camelize될수 있어야 하므로 소문자로 변환한다.
        //$this->attributes = \Limepie\array_change_key_case_recursive($array, \CASE_LOWER);

        if ($array instanceof \Limepie\ArrayObject) {
            $this->attributes = $array->attributes;
        } elseif ($array instanceof \stdClass) {
            $this->attributes = (array) $array;
        } else {
            $this->attributes = $array;
        }
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    public function __debugInfo() : array
    {
        return $this->attributes;
    }

    public function __call(string $name, array $arguments = [])
    {
        if (0 === \strpos($name, 'get')) { // get field
            return $this->buildGetColumn($name, $arguments);
        }

        throw new \Limepie\Exception('"' . $name . '" method not found', 1999);
    }

    public function __get($name)
    {
        if (true === isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        throw new \Limepie\Exception(\get_called_class() . ': Column "' . $name . '" not found #9', 1999);
    }

    public function isExists() : bool
    {
        return $this->attributes ? true : false;
    }

    public function jsonSerialize() : array
    {
        return $this->attributes;
    }

    public function serialize() : string
    {
        return \serialize($this->attributes);
    }

    public function unserialize($data)
    {
        $this->attributes = \unserialize($data);
    }

    public function merge(array $array)
    {
        $attributes = \Limepie\array_change_key_case_recursive($array, \CASE_LOWER);
        $this->attribute += $attributes;
    }

    public function buildGetColumn($name, $arguments)
    {
        // field name
        $isOrEmpty = false;
        $isOrNull  = false;
        $default   = null;

        if (true === \array_key_exists(0, $arguments)) {
            $isOrNull  = true;
            $default   = $arguments[0];
            $fieldName = \Limepie\decamelize(\substr($name, 3));
        } elseif (false !== \strpos($name, 'OrNull')) {
            $isOrNull  = true;
            $fieldName = \Limepie\decamelize(\substr($name, 3, -6));
        } elseif (false !== \strpos($name, 'OrEmpty')) {
            $isOrEmpty = true;
            $fieldName = \Limepie\decamelize(\substr($name, 3, -7));
        } else {
            $fieldName = \Limepie\decamelize(\substr($name, 3));
        }

        if (!$name) {
            throw new \Limepie\Exception(\get_called_class() . ': Column "' . $fieldName . '" not found #2', 1999);
        }

        //if (true === isset($this->attributes[$fieldName])) {
        if (
            true === \array_key_exists($fieldName, $this->attributes)
        ) {
            if (true === \is_null($this->attributes[$fieldName])) {
                if (true === \is_array($default)) {
                    return new ArrayObject($default);
                }
                //}
                return $default;
            }
            // 배열일 경우에는 arrayobject에 담아 리턴
            if (true === \is_array($this->attributes[$fieldName])) {
                return new ArrayObject($this->attributes[$fieldName]);
            }

            return $this->attributes[$fieldName];
        }

        // if(true === array_key_exists($fieldName, $this->attributes)) {
        //     return $default;
        // } else {
        if (true === $isOrEmpty) {
            // 필드가 아니면 배열을 리턴하는것은 모델이므로, 일단 삭제
            // if (false === \in_array($fieldName, $this->allFields, true)) { // model
            //     // ??
            //     return [];
            // }

            return ''; // column
        }

        if (false === $isOrNull && false === $isOrEmpty) {
            // unknown column
            throw new \Limepie\Exception(\get_called_class() . ': Column "' . $fieldName . '" not found #1', 1999);
        }

        return null;
    }

    public function rewind()
    {
        \reset($this->attributes);
    }

    public function current()
    {
        return \current($this->attributes);
    }

    public function key(?string $keyName = null)
    {
        return \key($this->attributes);
    }

    public function next()
    {
        return \next($this->attributes);
    }

    public function valid()
    {
        $key = \key($this->attributes);

        return null !== $key && false !== $key;
    }

    public function count()
    {
        return \count($this->attributes);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function unset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        if (false === \array_key_exists($offset, $this->attributes)) {
            $message = 'Undefined offset: ' . $offset;
            $code    = 234;

            throw new \Limepie\Exception($message, $code);
        }

        return $this->attributes[$offset];
        //return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    public function toArray(\Closure $callbackFunction = null)
    {
        $attributes = $this->buildArray($this);

        if ($callbackFunction instanceof \Closure) {
            return $callbackFunction($attributes);
        }

        return $attributes;
    }

    public function toJson($option = 0) : string
    {
        return \json_encode($this->buildArray($this), $option);
    }

    private function buildArray($d)
    {
        if ($d instanceof \Limepie\ArrayObject) {
            $d = \array_map([__CLASS__, __METHOD__], $d->attributes);
        }

        if (true === \is_object($d)) {
            $d = \get_object_vars($d);
        }

        if (true === \is_array($d)) {
            return \array_map([__CLASS__, __METHOD__], $d);
        }

        return $d;
    }
}
