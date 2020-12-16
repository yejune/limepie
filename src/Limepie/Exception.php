<?php declare(strict_types=1);

namespace Limepie;

class Exception extends \Exception
{
    // public $isLocal = false;

    // public $localTrace;

    public $displayMessage;

    public $location;

    public function __construct($e, int $code = 0)
    {
        if (true === \is_object($e) && $e instanceof \Throwable) {
            $this->setMessage($e->getMessage());
            $code = $code ?: $e->getCode();

            if (true === \strpos($e->getFile(), '/limepie/src/')) {
                $trace = $this->getLastTrace();
            } else {
                $trace = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }
        } elseif (true === \is_string($e)) {
            $this->setMessage($e);
            $trace = $this->getLastTrace();
        } else {
            throw new \Exception('exception error');
        }

        if ($code) {
            $this->setCode($code);
        }

        if ($trace ?? false) {
            if ($trace['line'] ?? false) {
                $this->setLine($trace['line']);
            }

            if ($trace['file'] ?? false) {
                $this->setFile($trace['file']);
            }
        }
    }

    public function __toString()
    {
        return $this->getMessage() . ' in ' . $this->getFile() . ' on line ' . $this->getLine();
    }

    public function getLastTrace()
    {
        $traces = $this->getTrace();

        foreach ($traces as $trace) {
            if (true === isset($trace['file'])) {
                if (false === \strpos($trace['file'], '/limepie/src/')) {
                    return $trace;
                }
            }
        }
    }

    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setDisplayMessage($message)
    {
        $this->displayMessage = $message;

        return $this;
    }

    public function getDisplayMessage()
    {
        return $this->displayMessage ?: $this->getMessage();
    }

    public function setLine(int $line)
    {
        $this->line = $line;

        return $this;
    }

    public function setFile(string $file)
    {
        $this->file = $file;

        return $this;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function setTrace(array $trace)
    {
        $this->trace = $trace;

        return $this;
    }

    // public function getTraces()
    // {
    //     if (true === $this->isLocal) {
    //         $traces = $this->localTrace;
    //     } else {
    //         $traces = parent::getTrace();
    //     }

    //     return $traces;
    // }

    // public function getTracesString()
    // {
    //     if (true === $this->isLocal) {
    //         $message = $this->getTraceAsStringFromLocal();
    //     } else {
    //         $message = $this->getTraceAsString();
    //     }

    //     return $message;
    // }

    // public function getTraceAsStringFromLocal() : string
    // {
    //     $rtn   = '';
    //     $count = 0;

    //     foreach ($this->localTrace as $frame) {
    //         $args = '';

    //         if (true === isset($frame['args'])) {
    //             $args = [];

    //             foreach ($frame['args'] as $arg) {
    //                 if (true === \is_string($arg)) {
    //                     $args[] = "'" . $arg . "'";
    //                 } elseif (true === \is_array($arg)) {
    //                     $args[] = 'Array';
    //                 } elseif (null === $arg) {
    //                     $args[] = 'NULL';
    //                 } elseif (true === \is_bool($arg)) {
    //                     $args[] = ($arg) ? 'true' : 'false';
    //                 } elseif (true === \is_object($arg)) {
    //                     $args[] = \get_class($arg);
    //                 } elseif (true === \is_resource($arg)) {
    //                     $args[] = \get_resource_type($arg);
    //                 } else {
    //                     $args[] = $arg;
    //                 }
    //             }
    //             $args = \implode(', ', $args);
    //         }
    //         $rtn .= \sprintf(
    //             "#%s %s(%s): %s(%s)\n",
    //             $count,
    //             $frame['file'] ?? 'unknown file',
    //             $frame['line'] ?? 'unknown line',
    //             (true === isset($frame['class'])) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'],
    //             $args
    //         );
    //         $count++;
    //     }

    //     return $rtn;
    // }

    public function throw() : void
    {
        exit($this->__toString());
    }
}
