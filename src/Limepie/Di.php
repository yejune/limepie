<?php declare(strict_types=1);

namespace Limepie;

class Di
{
    protected static $instance;

    private $properties = [];

    private $instances = [];

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

        if (0 === \strpos($name, 'push')) {
            return Di::instance()->pushBuild($name, $arguments);
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

    public function hasInstance(string $key) : bool
    {
        return \array_key_exists($key, $this->instances);
    }

    public function setInstance(string $key) : void
    {
        $this->instances[$key] = true;
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

        if (false === Di::instance()->hasInstance($key) && true === Di::isCallableClosure($value)) {
            $value = $value();
            Di::instance()->setInstance($key);
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

        // ERRORCODE: 40004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 40004);
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

        // ERRORCODE: 40004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 40004);
    }

    public function setBuild($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));
        $this->setProperty($fieldName, $arguments[0]);
    }

    public function pushBuild($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 4));

        if (Di::instance()->hasProperty($fieldName)) {
            $stack   = Di::instance()->getProperty($fieldName);
            $stack[] = $arguments[0];
        } else {
            $stack = [
                $arguments[0],
            ];
        }
        $this->setProperty($fieldName, $stack);
    }

    public function getBuild($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        $default = null;

        if (true === \array_key_exists(0, $arguments)) {
            $default = $arguments[0];

            if (true === \is_array($default)) {
                $default = new \Limepie\ArrayObject($default);
            }
        }

        if ($this->hasProperty($fieldName)) {
            return $this->runProperty($fieldName) ?? $default;
        }

        $fieldName = \str_replace('_', '-', $fieldName);

        if ($this->hasProperty($fieldName)) {
            return $this->runProperty($fieldName) ?? $default;
        }

        if (true === \array_key_exists(0, $arguments)) {
            return $default;
        }

        throw new \Limepie\Exception('"' . $fieldName . '" service provider not found', 40000);
    }
}
