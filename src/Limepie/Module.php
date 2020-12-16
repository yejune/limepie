<?php declare(strict_types=1);

namespace Limepie;

class Module
{
    protected function getSpec($path)
    {
        return \Limepie\yml_parse_file($path);
    }
}
