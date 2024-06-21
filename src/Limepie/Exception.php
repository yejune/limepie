<?php

declare(strict_types=1);

namespace Limepie;

class Exception extends \Exception
{
    // public $isLocal = false;

    // public $localTrace;

    public $displayMessage;

    public $displayMessageLine;

    public $displayMessageFile;

    public $displayMessageTraces = [];

    public $debugMessage;

    public $debugMessageLine;

    public $debugMessageFile;

    public $debugMessageTraces = [];

    public $location;

    public $class;

    public $function;

    public $type;

    public $trace;

    public $payload = [];

    public function __construct($e, int $code = 0)
    {
        if ($e instanceof \Throwable) {
            $this->setMessage($e->getMessage());
            $this->setCode($e->getCode());

            if (false === \strpos($e->getFile(), '/limepie/src/')) {
                $trace = [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            } else {
                $trace = $this->getLastTrace();
            }
        } elseif (true === \is_string($e)) {
            $this->setMessage($e);

            if (false !== \strpos($this->getFile(), '/limepie/src/')) {
                $trace = $this->getLastTrace();
            }
        } else {
            throw new \Exception('exception error');
        }

        if ($trace ?? false) {
            if ($trace['line'] ?? false) {
                $this->setLine($trace['line']);
            }

            if ($trace['file'] ?? false) {
                $this->setFile($trace['file']);
            }

            if ($trace['function'] ?? false) {
                $this->setFunction($trace['function']);
            }

            if ($trace['class'] ?? false) {
                $this->setClass($trace['class']);
            }

            if ($trace['type'] ?? false) {
                $this->setType($trace['type']);
            }
        }

        if (0 < $code) {
            $this->setCode($code);
        }
    }

    public function __toString()
    {
        return $this->getMessage() . ' in ' . $this->getFile() . ' on line ' . $this->line;
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

    public function getLocation($default = null)
    {
        if ($this->location) {
            return $this->location;
        }

        if ($default) {
            return $default;
        }
    }

    public function setDisplayMessage($message, $file = null, $line = null, $displayMessageDescription = null)
    {
        $this->displayMessage = $message;

        if (null !== $file) {
            $this->displayMessageFile = $file;
        }

        if (null !== $line) {
            $this->displayMessageLine = $line;
        }

        $this->displayMessageTraces[] = [
            'message'     => $this->displayMessage,
            'file'        => $this->displayMessageFile,
            'line'        => $this->displayMessageLine,
            'description' => $displayMessageDescription,
        ];

        return $this;
    }

    public function getDisplayMessage()
    {
        return $this->displayMessage ?: $this->getMessage();
    }

    public function setDisplayMessageLine($line)
    {
        $this->displayMessageLine = $line;

        return $this;
    }

    public function getDisplayMessageLine()
    {
        return $this->displayMessageLine; // ?: $this->getLine();
    }

    public function setDisplayMessageFile($file)
    {
        $this->displayMessageFile = $file;

        return $this;
    }

    public function getBestMessage()
    {
        if ($this->displayMessage) {
            return $this->displayMessage;
        }

        if ($this->message) {
            return $this->message;
        }
    }

    public function getDisplayMessageFile()
    {
        return $this->displayMessageFile; // ?: $this->getFile();
    }

    public function setDebugMessage($message, $file = null, $line = null, $debugMessageDescription = '')
    {
        $this->debugMessage = $message;

        if (null !== $file) {
            $this->debugMessageFile = $file;
        }

        if (null !== $line) {
            $this->debugMessageLine = $line;
        }

        $this->debugMessageTraces[] = [
            'message'     => $this->debugMessage,
            'file'        => $this->debugMessageFile,
            'line'        => $this->debugMessageLine,
            'description' => $debugMessageDescription,
        ];

        return $this;
    }

    public function getDebugMessage()
    {
        return $this->debugMessage ?: $this->getMessage();
    }

    public function setDebugMessageLine($line)
    {
        $this->debugMessageLine = $line;

        return $this;
    }

    public function getDebugMessageLine()
    {
        return $this->debugMessageLine ?: $this->getLine();
    }

    public function setDebugMessageFile($file)
    {
        $this->debugMessageFile = $file;

        return $this;
    }

    public function getDebugMessageFile()
    {
        return $this->debugMessageFile ?: $this->getFile();
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

    public function setFunction($function)
    {
        $this->function = $function;

        return $this;
    }

    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function payload($data)
    {
        $this->payload = $data;

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

    public function toString() : string
    {
        return $this->__toString();
    }
}
