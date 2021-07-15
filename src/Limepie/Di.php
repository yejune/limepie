<?php declare(strict_types=1);

namespace Limepie;

class Di
{
    protected static $instance;

    private $properties = [];

    private $isInstance = false;

    /**
     * reset variables.
     */
    public function __destruct()
    {
        //Di::instance()->properties = null;
    }

    public static function __callStatic($name, $arguments)
    {
        if (0 === \strpos($name, 'get')) {
            return Di::instance()->getBuild($name, $arguments);
        }

        if (0 === \strpos($name, 'set')) {
            return Di::instance()->setBuild($name, $arguments);
        }

        throw new \Limepie\Exception('static method not found: ' . $name);
    }

    /**
     * Singleton instance.
     */
    public static function instance() : Di
    {
        if (null === Di::$instance) {
            Di::$instance = new Di();

            // default it automatically
            // Di::$instance->setProperty('request', function() {
            //     return new Request;
            // });
            // Di::$instance->setProperty('response', function() {
            //     return new Response;
            // });
            // Di::$instance->setProperty('dispatcher', function() {
            //     return new Dispatcher;
            // });
        }

        return Di::$instance;
    }

    public function getInstance() : Di
    {
        return Di::$instance;
    }

    public function hasProperty(string $key) : bool
    {
        return \array_key_exists($key, $this->properties);
    }

    public function setProperty(string $key, $value) : void
    {
        $this->properties[$key] = $value;
    }

    public function getProperty(string $key)
    {
        return $this->properties[$key];
    }

    public function setProperties(array $properties) : void
    {
        $this->properties = $properties;
    }

    public function getProperties() : array
    {
        return $this->properties;
    }

    public function runProperty($key)
    {
        $value = Di::instance()->getProperty($key);

        if (false === $this->isInstance && true === Di::isCallableClosure($value)) {
            // TODO: invoke method가 있으면 callable가 true임, invoke를 계속 실행하게 됨
            // => isInstance check
            $value             = $value();
            $value->isInstance = true;
            Di::instance()->setProperty($key, $value);
        }

        if (true === \is_array($value)) {
            return new \Limepie\ArrayObject($value);
        }

        return $value;
    }

    public static function has($className)
    {
        return Di::instance()->hasProperty($className);
    }

    public static function register(string $className, $classObject, bool $renew = false) : void
    {
        if (false === Di::instance()->hasProperty($className) || true === $renew) {
            Di::instance()->setProperty($className, $classObject);
        }
    }

    public static function set(string $key, $value) : void
    {
        Di::instance()->setProperty($key, $value);
    }

    public static function isCallableClosure($value) : bool
    {
        //return true === \is_object($value) && ($value instanceof \Closure);

        return true === \is_object($value) && true === \is_callable($value);
    }

    public static function raw(string $key)
    {
        if (true === Di::instance()->hasProperty($key)) {
            return Di::instance()->getProperty($key);
        }

        // ERRORCODE: 10004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 10004);
    }

    public static function merge($arg)
    {
        if (true === \is_array($arg)) {
            Di::instance()->setProperties($arg + Di::instance()->getProperties());
        } else {
            if (1 < \count(\func_get_args())) {
                $val = \func_get_arg(1);

                if (true === \is_array($val)) {
                    if (false === Di::instance()->hasProperty($arg)) {
                        Di::instance()->setProperty($arg, []);
                    }
                    Di::instance()->setProperty($arg, $val + Di::instance()->getProperty($arg));
                } else {
                    Di::instance()->setProperty($arg, $val);
                }
            }
        }
    }

    public static function get(string $key)
    {
        if (true === Di::instance()->hasProperty($key)) {
            $value = Di::instance()->getProperty($key);

            if (true === Di::isCallableClosure($value)) {
                // TODO: invoke method가 있으면 callable가 true임, invoke를 계속 실행하게 됨
                $value = $value();
                Di::instance()->setProperty($key, $value);
            }

            return $value;
        }

        // ERRORCODE: 10004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 10004);
    }

    public function setBuild($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        if (true === isset($arguments[1]) && true === $arguments[1]) {
            if (Di::instance()->hasProperty($fieldName)) {
                $tmp   = Di::instance()->getProperty($fieldName);
                $tmp[] = $arguments[0];
            } else {
                $tmp = [
                    $arguments[0],
                ];
            }
            $this->setProperty($fieldName, $tmp);
        } else {
            $this->setProperty($fieldName, $arguments[0]);
        }
    }

    public function getBuild($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        $default = null;

        if (true === \array_key_exists(0, $arguments)) {
            $default = $arguments[0];
        }

        if ($this->hasProperty($fieldName)) {
            return $this->runProperty($fieldName) ?? $default;
        }
        $fieldName = \str_replace('_', '-', $fieldName);

        if ($this->hasProperty($fieldName)) {
            return $this->runProperty($fieldName) ?? $default;
        }

        if (true === \array_key_exists(0, $arguments)) {
            return $arguments[0];
        }

        throw new \Limepie\Exception('"' . $fieldName . '" service provider not found', 1999);
    }
}
