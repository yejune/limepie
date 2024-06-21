<?php

namespace Limepie;

class Singleton
{
    private static $instance;

    private $meta = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function modify($meta, $value = null)
    {
        if (null === $meta) {
        } else {
            if (true === \is_array($meta)) {
                $this->meta = $meta + $this->meta;
            } else {
                if (null === $value) {
                    return new \Limepie\ArrayObject($this->meta[$meta] ?? []);
                }
                $this->meta[$meta] = $value;
            }
        }

        return $this;
    }
}
