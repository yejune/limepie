<?php declare(strict_types=1);

namespace Limepie;

class Mobject extends \ArrayObject
{
    public function __construct($array)
    {
        parent::__construct($array, \ArrayObject::STD_PROP_LIST|\ArrayObject::ARRAY_AS_PROPS);
    }

    public function __call($key, $args)
    {
        if (0 === \strpos($key, 'get')) {
            $name = \Limepie\decamelize(\substr($key, 3));
            return $this[$name] ?? null;
        }
    }

    // public function importObj($class,  $array = []){
    //     $this->___class = $class;
    //     if(count($array) > 0){
    //         $this->import($array);
    //     }
    //     return $this;
    // }

    // public function import($input)
    // {
    //     $this->exchangeArray($input);
    //     return $this;
    // }

    public function toArray(\Closure $callback = null)
    {
        $variables = $this->objectToArray($this->getArrayCopy());

        if (true === isset($callback) && $callback) {
            return $callback($variables);
        }

        return $variables;
    }

    public function toJson($option = 0)
    {
        return \json_encode($this->buildArray($this), $option);
    }
    public function objectToArray ($object) {
        $o = [];
        foreach ($object as $key => $value) {
           $o[$key] = is_object($value) ? (array) $value: $value;
        }
        return $o;
    }

    // public function toArraytoArray(\Closure $callback = null)
    // {
    //     $attributes = $this->buildArray($this);

    //     if (true === isset($callback) && $callback) {
    //         return $callback($attributes);
    //     }

    //     return $attributes;
    // }


    // private function buildArray($d)
    // {
    //     // if ($d instanceof \Limepie\Mobject) {
    //     //     $d = \array_map([__CLASS__, __METHOD__], $d->attributes);
    //     // }

    //     if (true === \is_object($d)) {
    //         $d = \get_object_vars($d);
    //     }

    //     if (true === \is_array($d)) {
    //         return \array_map([__CLASS__, __METHOD__], $d);
    //     }
    //     // Return array
    //     return $d;
    // }
}
