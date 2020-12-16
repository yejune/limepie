<?php declare(strict_types=1);

namespace Limepie;

class Bootstrap
{
    public $di;

    public function __invoke($app) : Application
    {
        return $this->run($app);
    }

    protected function initialize($app) : void
    {
    }

    private function run($app) : Application
    {
        $this->initialize($app);

        return $app;
    }
}
