<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

use Limepie\Exception;

class Deadlock extends Exception
{
    private int $retryCount;

    public function __construct($message = '', $code = 40001, $retryCount = 3)
    {
        $this->retryCount = $retryCount;
        parent::__construct($message, $code);
    }

    public function getRetryCount() : int
    {
        return $this->retryCount;
    }

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
