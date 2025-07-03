<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

use Limepie\Exception;

class Transaction extends Exception
{
    // public function __construct($e, int $code = 0)
    // {
    //     parent::__construct($e, $code);

    //     // set real message
    //     if (true) {
    //         $trace = $this->getLastTrace();

    //         if ($trace) {
    //             if ($trace['line'] ?? false) {
    //                 $this->setLine($trace['line']);
    //             }

    //             if ($trace['file'] ?? false) {
    //                 $this->setFile($trace['file']);
    //             }
    //         }
    //     }
    // }

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
