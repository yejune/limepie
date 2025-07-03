<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

use Limepie\Exception;

class OptimisticLock extends Exception
{
    public function getLastTrace()
    {
        $traces = $this->getTrace();

        foreach ($traces as $trace) {
            if ('Limepie\Pdo\Mysql' !== $trace['class']) {
                return $trace;
            }
        }
    }
}
