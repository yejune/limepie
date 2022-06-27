<?php declare(strict_types=1);

namespace Limepie;

class ArrayObject implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable //, \Serializable
{
    public $attributes = [];

    public function __construct(ArrayObject|array|\stdClass|null $array)
    {
        if ($array instanceof \Limepie\ArrayObject) {
            $this->attributes = $array->attributes;
        } elseif ($array instanceof \stdClass) {
            $this->attributes = (array) $array;
        } elseif (true === \is_array($array)) {
            $this->attributes = $array;
        }
    }

    public function __serialize() : array
    {
        return $this->attributes;
    }

    public function __unserialize(array $data) : void
    {
        $this->attributes = $data;
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

        if (0 === \strpos($name, 'set')) { // get field
            return $this->buildSetColumn($name, $arguments);
        }

        throw new \Limepie\Exception('"' . $name . '" method not found', 500);
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function __get($name)
    {
        if (true === isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        throw new \Limepie\Exception(\get_called_class() . ': Column "' . $name . '" not found #9', 500);
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

    public function reverse(bool $preserveKeys = false) : self
    {
        return new static(\array_reverse($this->attributes, $preserveKeys));
    }

    public function buildSetColumn($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        $this->attributes[$fieldName] = $arguments[0];

        return $this;
    }

    public function buildGetColumn($name, $arguments)
    {
        // field name
        $isOrEmpty = false;
        $isOrNull  = false;
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        if (!$name) {
            throw new \Limepie\Exception(\get_called_class() . ': Column "' . $fieldName . '" not found #2', 500);
        }

        if (
            (
                false === isset($this->attributes[$fieldName])
                || true === \is_null($this->attributes[$fieldName])
            )
            && true === \array_key_exists(0, $arguments)
        ) {
            $default = $arguments[0];

            if (true === \is_array($default)) {
                return new ArrayObject($default);
            }

            return $default;
        }

        if (
            true === \array_key_exists($fieldName, $this->attributes)
        ) {
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
            throw new \Limepie\Exception(\get_called_class() . ': Column "' . $fieldName . '" not found #1', 500);
        }

        return null;
    }

    #[\ReturnTypeWillChange]
    public function rewind()// : void
    {
        \reset($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return \current($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function key(?string $keyName = null)// : mixed
    {
        return \key($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        return \next($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function valid()// : bool
    {
        $key = \key($this->attributes);

        return null !== $key && false !== $key;
    }

    #[\ReturnTypeWillChange]
    public function count()// : int
    {
        return \count($this->attributes);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)// : void
    {
        if (null === $offset) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function unset($offset)
    {
        $keys = \func_get_args();

        foreach ($keys as $key) {
            if (false === \array_key_exists($key, $this->attributes)) {
                throw new \Limepie\Exception($key . ': unset key not found');
            }
            unset($this->attributes[$key]);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)// : bool
    {
        return isset($this->attributes[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)// : void
    {
        $keys = \func_get_args();

        foreach ($keys as $key) {
            if (false === \array_key_exists($key, $this->attributes)) {
                throw new \Limepie\Exception($key . ': unset key not found');
            }
            unset($this->attributes[$key]);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)// : mixed
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
        $attributes = $this->buildArray($this);

        return \json_encode($attributes, $option);
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

    // $dateModels->usort(function($a, $b) {
    //     return (new \Datetime($a->getDate()))->format('Y-m-d') <=> (new \Datetime($b->getDate()))->format('Y-m-d');
    // });
    public function usort(callable $compareFunc) : self
    {
        if (true !== \usort($this->attributes, $compareFunc)) {
            throw new \Limepie\Exception('usort() failed');
        }

        return $this;
    }

    public function first()
    {
        return $this->attributes[\array_key_first($this->attributes)];
    }

    public function last()
    {
        return $this->attributes[\array_key_last($this->attributes)];
    }

    public function children(array $data, array $maps = [], $params = [])
    {
        $request   = Di::getRequest();
        $children  = &$this->attributes;
        $attribute = [];

        foreach ($maps as $step) {
            $attribute = &$children[$request->getPath(0, $step)]; // ['children'];
            $children  = &$attribute['children'];
        }

        $attribute['params']   = $params + ($attribute['params'] ?? []);
        $attribute['children'] = $data;

        $children = &$attribute;
        Di::setMenus($this->attributes);

        return $this;
    }
}
