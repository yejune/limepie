<?php declare(strict_types=1);

namespace Limepie;

abstract class Pdo extends \Pdo
{
    abstract public function gets($statement, $bindParameters = [], $type = true);

    abstract public function get($statement, $bindParameters = [], $type = true);

    abstract public function get1($statement, $bindParameters = []);

    abstract public function set($statement, $bindParameters = []);

    abstract public function setAndGetSequnce($statement, $bindParameters = []);
}
