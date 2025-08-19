<?php declare(strict_types=1);

namespace Limepie;

class ArrayFlatten
{
    public $store = [];

    public function __construct(array $arr = [])
    {
        $this->store = $this->recursive_array_flatten($arr);
    }

    public function gets()
    {
        return $this->recursive_array_unflatten($this->store);
    }

    public function recursive_array_flatten(array $list = [], string $prefix = '')
    {
        $result = [];

        foreach ($list as $name => $value) {
            $newPrefix = ($prefix) ? $prefix . '[' . $name . ']' : $name;

            if (true === \is_array($value)) {
                if (true === $this->is_file_array($value, false)) {
                    $result[$newPrefix] = $value;
                } else {
                    $result = $result + $this->recursive_array_flatten($value, $newPrefix);
                }
            } else {
                $result[$newPrefix] = $value;
            }
        }

        return $result;
    }

    public function recursive_array_unflatten(array $array = [])
    {
        $return = [];

        foreach ($array as $string => $value) {
            $token = \strtok(\str_replace(']', '', $string), '[');
            $ref   = &$return;

            while (false !== $token) {
                if (!isset($ref[$token])) {
                    $ref[$token] = [];
                }
                $ref   = &$ref[$token];
                $token = \strtok('[');
            }

            $ref = $value;
        }

        return $return;
    }

    public function get($key)
    {
        return $this->store[$key];
    }

    public function put($key, $value)
    {
        return $this->store[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->store[$key]);
    }

    public function is_file_array($array = [], $isMulti = false) : bool
    {
        if (true === \is_array($array)) {
            if (
                true === isset($array['name'])
                && true === isset($array['type'])
                //&& true === isset($array['tmp_name'])
                && true === isset($array['error'])
                && true === isset($array['size'])
            ) {
                return true;
            }

            if (true === $isMulti) {
                foreach ($array as $file) {
                    if (
                        true === \is_array($file)
                        && true === isset($file['name'])
                        && true === isset($file['type'])
                        //&& true === isset($file['tmp_name'])
                        && true === isset($file['error'])
                        && true === isset($file['size'])
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
