<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

class Execute extends \Limepie\Exception
{
    public $error;

    public $statement;

    public $binds = [];

    public function __construct($e, string $statement = '', array $binds = [])
    {
        parent::__construct($e);

        if ($statement) {
            $this->setStatement($statement);
        }

        if ($binds) {
            $this->setBinds($binds);
        }

        $current = $this->getLastTrace();

        if ($current) {
            if (true === isset($current['file'])) {
                $this->setFile($current['file']);
            }

            if (true === isset($current['line'])) {
                $this->setLine($current['line']);
            }
        }
        $this->setMessage($this->getErrorFormat());
    }

    public function __toString()
    {
        return $this->getMessage() . ' in ' . $this->getFile() . ' on line ' . $this->getLine() . ' ' . $this->getErrorFormat();
    }

    public function setStatement($statement)
    {
        $this->statement = $statement;

        return $this;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function setBinds(array $binds = [])
    {
        $this->binds = $binds;
    }

    public function getBinds()
    {
        return $this->binds;
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        // error가 우선한다.
        if ($this->statement && !$this->error) {
            return $this->getErrorFormat();
        }

        return $this->error;
    }

    private function getErrorFormat()
    {
        $query = $this->getStatement();
        $binds = $this->getBinds();

        foreach ($binds as $key => $value) {
            $binds[] = $key . ' => ' . $value;
        }

        return $this->getMessage() . ",\n" . $query . ($binds ? ', [' . \implode(', ', $binds) . ']' : '');
    }
}
