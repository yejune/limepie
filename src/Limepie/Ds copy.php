<?php

namespace Limepie;

// data structure
class Ds implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable // , \Serializable
{
    private $attributes = [];

    public $keys = [];

    public $position;

    private static $instance = [];

    public $id = 'root';

    public $parent_id;

    public function __construct()
    {
    }

    public function resetKeys()
    {
        $this->keys = [];

        return $this;
    }

    public function keys($keys)
    {
        $this->keys = $keys;

        return $this;
    }

    public function setParentId($id)
    {
        $this->parent_id = $id;

        return $this;
    }

    public function selfInjection(mixed $data) : mixed
    {
        if (\is_array($data)) {
            return static::getInstance(\implode('_', $this->keys))->injection($data)->setParentId($this->id)->keys($this->keys);
        }

        return $data;
    }

    public function first()
    {
        return $this->selfInjection($this->attributes[\array_key_first($this->attributes)]);
    }

    public function last()
    {
        return $this->selfInjection($this->attributes[\array_key_last($this->attributes)]);
    }

    public function add(array|string|int $key, mixed $value = null) : self
    {
        $position = static::getInstance($this->parent_id)->position;

        // \pr($this->position, $position);

        if (null !== $position) {
            $currentAttributes = &$this->attributes;
            $attributes        = &static::getInstance('root')->getCurrentAttributes();
            $attributes        = &$attributes[$position];
        } else {
            $currentAttributes = &$this->getCurrentAttributes();
            $attributes        = &static::getInstance('root')->attributes;
        }
        // \pr($key, $value, $this->attributes);
        // \pr($this->attributes, $attributes, $this->keys, $this->oldKeys, $this->position, $this->id, $key, $value);

        // $key가 배열인 경우
        if (\is_array($key)) {
            if (null !== $value) {
                throw new \InvalidArgumentException('When the key is an array, value must not be provided.');
            }

            $currentAttributes[] = $key;

            return $this;
        }

        if (null === $value) {
            $currentAttributes[] = $key;
            $attributes[]        = $key;
        } else {
            $currentAttributes[$key] = $value;
            $attributes[$key]        = $value;
        }

        return $this;
    }

    public function modify(array|string|int $key, mixed $value = null) : self
    {
        $attributes = &$this->getCurrentAttributes();

        // $key가 배열인 경우
        if (\is_array($key)) {
            if (null !== $value) {
                throw new \InvalidArgumentException('When the key is an array, value must not be provided.');
            }
            $attributes = \array_replace_recursive($attributes, $key);

            return $this;
        }

        // 키-값 쌍을 수정하거나 추가
        $attributes[$key] = $value;

        return $this;
    }

    public function get(string|int|null $key = null) : mixed
    {
        $attributes = &$this->getCurrentAttributes();
        // \pr($this->keys, $this->selfInjection($attributes));

        // $key가 null이면 현재 데이터의 전체를 반환
        if (null === $key) {
            return $this->selfInjection($attributes);
        }

        return $this->selfInjection($attributes[$key] ?? null);
    }

    public function set(array|string|int $key, mixed $value = null) : self
    {
        $attributes = &$this->getCurrentAttributes();

        // $key가 배열인 경우
        if (\is_array($key)) {
            if (null !== $value) {
                throw new \InvalidArgumentException('When the key is an array, value must not be provided.');
            }

            foreach ($key as $k => $v) {
                $attributes[$k] = $v;
            }

            return $this;
        }

        $attributes[$key] = $value;

        return $this;
    }

    public function remove()
    {
        $this->removeCurrentattributes();

        return $this;
    }

    public function injection($attributes, $value = null)
    {
        // 첫 번째 인자가 배열인 경우 병합
        if (\is_array($attributes)) {
            $this->attributes = $attributes + $this->attributes;
        }
        // 첫 번째 인자가 키로 사용될 수 있는 문자열이거나 정수인 경우
        elseif (\is_string($attributes) || \is_int($attributes)) {
            if (null !== $value) {
                $this->attributes[$attributes] = $value;
            } else {
                \pr($attributes, $value);

                // 유효한 키와 함께 null 값이 제공되면 예외 발생
                throw new \InvalidArgumentException('Value cannot be null when a valid key is provided.');
            }
        } elseif (null === $attributes) {
            return null;
            // 첫 번째 인자가 null인 경우 아무 작업도 수행하지 않음
        } else {
            // 첫 번째 인자가 배열, 문자열, 정수 외의 값인 경우 예외 발생
            throw new \InvalidArgumentException('Invalid argument type for attributes.');
        }

        return $this;
    }

    public static function getInstance($id = 'root')
    {
        if (false === isset(self::$instance[$id]) || null === self::$instance[$id]) {
            self::$instance[$id]     = new self();
            self::$instance[$id]->id = $id;
        }

        return self::$instance[$id];
    }

    public function &getCurrentAttributes($keys = [])
    {
        $attributes = &$this->attributes;

        // if (!$keys) {
        //     $keys = $this->keys;
        // }

        foreach ($this->keys as $innerKey) {
            $attributes = &$attributes[$innerKey];
        }
        // \pr(
        //     [
        //         'id'        => $this->id,
        //         'parent_id' => $this->parent_id,
        //         'keys'      => $this->keys,
        //         'data'      => $attributes,
        //     ]
        // );

        return $attributes;
    }

    private function buildArray($d)
    {
        if ($d instanceof self) {
            $d = $d->attributes;
        }

        if (true === \is_object($d)) {
            $d = \get_object_vars($d);
        }

        if (true === \is_array($d)) {
            return $d;

            return \array_map(__METHOD__, $d);
        }

        return $d;
    }

    public function toArray(\Closure $callbackFunction = null)
    {
        $attributes = &$this->getCurrentAttributes();

        $attributes = $this->buildArray($attributes);

        if ($callbackFunction instanceof \Closure) {
            return $callbackFunction($attributes);
        }

        return $attributes;
    }

    public function filter(\Closure $callback = null)
    {
        $attributes = &$this->getCurrentAttributes();

        if (true === isset($callback) && $callback) {
            return $callback($this);
        }

        return $attributes;
    }

    public function removeCurrentattributes()
    {
        $attributes = &$this->attributes;

        // 마지막 키를 얻기
        $lastKey = \end($this->keys);

        // 마지막 키를 제외한 모든 키를 순회
        foreach (\array_slice($this->keys, 0, -1) as $innerKey) {
            // if (!isset($attributes[$innerKey])) {
            //     return;  // 키가 없으면 종료
            // }
            $attributes = &$attributes[$innerKey];
        }

        // 마지막 위치의 데이터 삭제
        unset($attributes[$lastKey]);

        // $this->keys의 마지막 키도 삭제
        \array_pop($this->keys);

        return $this;
    }

    public function __get($name)
    {
        if (true === isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        throw new \Limepie\Exception(\get_called_class() . ': Column "' . $name . '" not found #9', 500);
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    public function __debugInfo() : array
    {
        return [
            'data'             => $this->attributes,
            'position'         => $this->position,
            'id'               => $this->id,
            'parent_id'        => $this->parent_id,
            'parent_poisition' => static::getInstance($this->parent_id)->position,
            'keys'             => $this->keys,
        ];
    }

    public function __call($key, $value = null)
    {
        switch ($key) {
            // case 'add':
            //     $attributes = &$this->getCurrentAttributes();

            //     if ($value) {
            //         if (isset($value[1])) {
            //             $attributes[$value[0]] = $value[1];
            //         } else {
            //             $attributes[] = $value[0];
            //         }
            //     }

            //     break;
            // case 'modify':
            //     $attributes = &$this->getCurrentAttributes();

            //     if ($value) {
            //         if (\is_array($value[0])) {
            //             $attributes = \array_replace_recursive($attributes, $value[0]);
            //         } else {
            //             $attributes = $value[0];
            //         }
            //     }

            //     break;
            // case 'get':
            //     $attributes = &$this->getCurrentAttributes();

            //     if ($value) {
            //         if (\is_array($value[1])) {
            //             $attributes = \array_replace_recursive($attributes, $value[0]);
            //         } else {
            //             $attributes = $value[0];
            //         }
            //     }

            //     return (new self)->injection($attributes);

            //     break;
            // case 'set':
            //     $attributes = &$this->getCurrentAttributes();

            //     if ($value) {
            //         if (\is_array($value[0])) {
            //             $attributes = \array_replace_recursive($attributes, $value[0]);
            //         } else {
            //             $attributes = $value[0];
            //         }
            //     }

            //     break;
            // case 'remove':
            //     $attributes = $this->removeCurrentattributes();

            //     break;
            default:
                if (0 === \strpos($key, 'getBy')) {
                    $key = \Limepie\decamelize(\substr($key, 5));

                    $position = static::getInstance($this->parent_id)->position;

                    // \pr($this->position, $position);

                    if (null !== $position) {
                        $currentAttributes = &$this->attributes;
                        $attributes        = &static::getInstance('root')->getCurrentAttributes();
                        $attributes        = &$attributes[$position];
                    } else {
                        $currentAttributes = &$this->getCurrentAttributes();
                        $attributes        = &static::getInstance('root')->attributes;
                    }

                    // $position = static::getInstance($this->parent_id)->position;

                    // // \pr($this->position, $position);

                    // if (null !== $position) {
                    //     $attributes = &static::getInstance('root')->getCurrentAttributes();

                    //     // \pr(static::getInstance('root')->keys, $this->keys, $attributes);
                    //     $attributes = &$attributes[$position];
                    // } else {
                    //     $attributes = &$this->getInstance('root')->attributes;
                    // }
                    // \pr($this->position, $this->keys, $data);

                    if ($value) {
                        if (\is_array($value[0])) {
                            $currentAttributes = \array_replace_recursive($currentAttributes, $value[0]);
                            $attributes        = \array_replace_recursive($attributes, $value[0]);
                        } else {
                            $currentAttributes[$key] = $value[0];
                            $attributes[$key]        = $value[0];
                        }
                    }

                    // $data = $this->attributes;

                    return $currentAttributes[$key];
                }

                if (0 === \strpos($key, 'get')) {
                    $key          = \Limepie\decamelize(\substr($key, 3));
                    $this->keys[] = $key;
                    $attributes   = &$this->getCurrentAttributes();

                    if ($value) {
                        if (\is_array($value[0])) {
                            $attributes = \array_replace_recursive($attributes, $value[0]);
                        } else {
                            $attributes = $value[0];
                        }
                    }
                } else {
                    $this->keys[] = $key;
                    $attributes   = &$this->getCurrentAttributes();

                    if ($value) {
                        if (\is_array($value[0])) {
                            $attributes = \array_replace_recursive($attributes, $value[0]);
                        } else {
                            $attributes = $value[0];
                        }
                    }
                }

                break;
        }

        return $this;
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        if (\is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset) : void
    {
        unset($this->attributes[$offset]);
    }

    public function offsetGet($offset) : mixed
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    public function current() : mixed
    {
        // \pr('current: ' . $this->position, $this->keys, $this->oldKeys);

        return $this->selfInjection($this->attributes[$this->position]);
    }

    public function key() : mixed
    {
        // \pr('key: ' . $this->position);

        return $this->position;
    }

    public $gijun = -1;

    public function next() : void
    {
        // \pr('next start: ' . $this->position);

        if (-1 === $this->gijun) {
            $this->gijun = \count($this->keys) - 1;
        }
        ++$this->position;

        if ($this->gijun > 0) {
            $this->keys = \array_slice($this->keys, 0, $this->gijun);
        }
        $this->keys[] = $this->position;

        if (!isset($this->attributes[$this->position])) {
            $this->position = null;
        }
        // \pr('next end: ' . $this->position);

        // if (isset($this->attributes[$this->position])) {
        //     ++\Limepie\Ds::getInstance()->pos;
        // }
    }

    // public $pos;

    public function rewind() : void
    {
        // \Limepie\Ds::getInstance()->pos = 0;
        // \pr('rewind start: ' . $this->position);
        $this->position = 0;
        $this->keys[]   = $this->position;
        // \pr('rewind end: ' . $this->position);
    }

    public function valid() : bool
    {
        // \pr('valid: ' . $this->position);

        return isset($this->attributes[$this->position]);
    }

    // public function rewind() : void
    // {
    //     $this->position = 0;
    //     \reset($this->attributes);
    // }

    // public function current() : mixed
    // {
    //     // $attributes = &$this->getCurrentAttributes();

    //     // \pr($attributes, $this->attributes);

    //     // \pr(\current($this->attributes));
    //     \pr('current ' . $this->id . ': ' . $this->position);
    //     \pr($this);

    //     return $this->selfInjection(\current($this->attributes));
    // }

    // public function key(?string $keyName = null) : mixed
    // {
    //     return \key($this->attributes);
    // }

    // public function next() : void
    // {
    //     ++$this->position;
    //     echo 'next ' . $this->id . ': ' . $this->position . PHP_EOL;
    //     \next($this->attributes);
    // }

    // public function valid() : bool
    // {
    //     $key = \key($this->attributes);

    //     return null !== $key && false !== $key;
    // }

    public function count() : int
    {
        return \count($this->attributes);
    }

    // For JsonSerializable
    public function jsonSerialize() : mixed
    {
        return $this->attributes;
    }

    // For Serializable
    public function __serialize()
    {
        return \serialize($this->attributes);
    }

    public function __unserialize($serialized)
    {
        $this->attributes = \unserialize($serialized);
    }
}
