<?php

declare(strict_types=1);

namespace Limepie\Pdo\Driver;

use Limepie\ArrayObject;
use Limepie\Pdo\Exception;
use Limepie\Timer;

class Mysql extends \Limepie\Pdo
{
    public $info = [];

    public $debug = false;

    public $deadlockLog = '/var/www/var/deadlock.log';

    public $isDeadlockLog = true;

    public $dbname;

    public $host;

    public $charset;

    public $readonly = false;

    /**
     * @param       $descriptor
     * @param mixed $connect
     * @param mixed $statement
     * @param mixed $bindParameters
     * @param mixed $ret
     */
    // public function connect(string $dsn, string $username = '', string $passwd = '', array $options =[])
    // {
    //     try {
    //         $this->_pdo = new \Pdo($dsn, $username, $password, $options);
    //     } catch (\Throwable $e) {
    //         throw $e;
    //     }
    // }

    public $rowCount = 0;

    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        $this->info = \parse_url($dsn);
        $parts      = \explode(';', $this->info['path']);

        foreach ($parts as $value) {
            $tmp             = \explode('=', $value, 2);
            $this->{$tmp[0]} = $tmp[1];
        }
        parent::__construct($dsn, $username, $passwd, $options);
    }

    public function setReadonly($flag = false)
    {
        $this->readonly = $flag;

        return $this;
    }

    /**
     * @param array $bindParameters
     * @param mixed $type           모델에서는 기본적으로 false로 넘겨 배열을 받고 pdo에 직접 접근할때는 true로 ArrayObject를 받음
     * @param mixed $statement
     *
     * @return array
     *
     * @throws \PDOException
     */
    public function gets($statement, $bindParameters = [], $type = true)
    {
        try {
            // return parent::fetchAll($statement, $mode, $bindParameters) ?: null;
            // pr(func_get_args());

            if ($this->debug) {
                Timer::start();
            }
            $mode = \PDO::FETCH_ASSOC;

            $stmt   = $this->execute($statement, $bindParameters);
            $mode   = $this->getMode($mode);
            $result = $stmt->fetchAll($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            if ($type) {
                return \Limepie\ato($result);
            }

            return $result;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
        }
    }

    /**
     * @param array $bindParameters
     * @param mixed $type           모델에서는 기본적으로 false로 넘겨 배열을 받고 pdo에 직접 접근할때는 true로 ArrayObject를 받음
     * @param mixed $statement
     *
     * @return array
     *
     * @throws \PDOException
     */
    public function get($statement, $bindParameters = [], $type = true)
    {
        try {
            // return parent::fetchOne($statement, $mode, $bindParameters) ?: null;

            if ($this->debug) {
                Timer::start();
            }
            $mode   = \PDO::FETCH_ASSOC;
            $stmt   = $this->execute($statement, $bindParameters);
            $mode   = $this->getMode($mode);
            $result = $stmt->fetch($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            if ($type) {
                return \Limepie\ato($result);
            }

            return $result;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
        }
    }

    /**
     * @param array $bindParameters
     * @param mixed $statement
     *
     * @return string
     *
     * @throws \PDOException
     */
    public function get1($statement, $bindParameters = [])
    {
        // pr(func_get_args());

        try {
            if ($this->debug) {
                Timer::start();
            }
            $mode   = \PDO::FETCH_ASSOC;
            $stmt   = $this->execute($statement, $bindParameters);
            $mode   = $this->getMode($mode);
            $result = $stmt->fetch($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            if (true === \is_array($result)) {
                foreach ($result as $key => $value) {
                    return $value;
                }
            }

            return false;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
        }
    }

    /**
     * @param array $bindParameters
     * @param mixed $statement
     *
     * @return bool
     *
     * @throws \PdoException
     */
    public function set($statement, $bindParameters = [])
    {
        if ($this->readonly) {
            throw new \Limepie\Exception('This is readonly mode: #1 ' . $this->getErrorMessage());
        }

        try {
            return $this->execute($statement, $bindParameters, true);
        } catch (\PDOException $e) {
            // \print_r($statement, $bindParameters);
            throw new Exception\Execute($e, $statement, $bindParameters);
        }
    }

    public function last_row_count()
    {
        return $this->rowCount;

        return $this->get1('SELECT FOUND_ROWS()');
    }

    /*
    \Peanut\Phalcon\Db::name('master')->sets(
        'insert into test (a,b,c,d) values (:a,:b,:c,:d)', [
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
        ]
    );
    =>
    insert into test(a,b,c,d) values(:a0, :b0, :c0, :d0),(:a1, :b1, :c1, :d1),(:a2, :b2, :c2, :d2)
    [
      [:a0] => 1
      [:b0] => 2
      [:c0] => 1
      [:d0] => 2
      [:a1] => 1
      [:b1] => 2
      [:c1] => 1
      [:d1] => 2
      [:a2] => 1
      [:b2] => 2
      [:c2] => 1
      [:d2] => 2
    ]
    */
    public function sets($statement, $bindParameters)
    {
        if ($this->readonly) {
            throw new \Limepie\Exception('This is readonly mode: #2 ' . $this->getErrorMessage());
        }

        if (
            0 < \count($bindParameters)
            && 1 === \preg_match('/(?P<control>.*)(?:[\s]+)values(?:[^\(]+)\((?P<holders>.*)\)/Us', $statement, $m)
        ) {
            $holders = \explode(',', \preg_replace('/\s/', '', $m['holders']));

            $newStatements     = [];
            $newBindParameters = [];

            foreach ($bindParameters as $key => $value) {
                $statements = [];

                foreach ($holders as $holder) {
                    $statements[]                      = $holder . $key;
                    $newBindParameters[$holder . $key] = $value[$holder];
                }
                $newStatements[] = '(' . \implode(', ', $statements) . ')';
            }
            $newStatement = $m['control'] . ' values ' . \implode(', ', $newStatements);

            try {
                if ($this->execute($newStatement, $newBindParameters, true)) {
                    return \count($bindParameters);
                }
            } catch (\PDOException $e) {
                throw new Exception\Execute($e, $statement, $bindParameters);
            }
        }

        return false;
    }

    /**
     * @param array $bindParameters
     * @param mixed $statement
     *
     * @return null|int
     */
    public function setAndGetSequnce($statement, $bindParameters = [])
    {
        if ($this->readonly) {
            throw new \Limepie\Exception('This is readonly mode: #3 ' . $this->getErrorMessage());
        }

        if (true === $this->set($statement, $bindParameters)) {
            return parent::lastInsertId();
        }

        return null;
    }

    public function closeCursor($oStm)
    {
        do {
            $oStm->fetchAll();
        } while ($oStm->nextRowSet());
    }

    public function call($statement, $bindParameters = [], $mode = \PDO::FETCH_ASSOC)
    {
        try {
            if ($this->debug) {
                Timer::start();
            }
            // $emul = parent::getAttribute(\PDO::ATTR_EMULATE_PREPARES);

            // if (false === $emul) {
            //     parent::setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            // }
            $stmt = $this->execute($statement, $bindParameters);

            // if (false === $emul) {
            //     parent::setAttribute(\PDO::ATTR_EMULATE_PREPARES, $emul);
            // }

            $mode = $this->getMode($mode);

            $streets = [];

            while ($stmt->columnCount()) {
                try {
                    $rows = $stmt->fetchAll($mode);

                    if ($rows) {
                        $streets = $rows;
                    }
                    $stmt->nextRowset();
                } catch (\PDOException $e) {
                    throw new Exception\Execute($e, $statement, $bindParameters);
                }
            }

            $stmt->closeCursor();

            if ($this->debug) {
                $timer = Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            if (true === \is_array($streets)) {
                foreach ($streets as $key => $value) {
                    foreach ($value as $row) {
                        return $row;
                    }
                }
            }

            return false;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
        }
    }

    public function xbegin()
    {
        parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);

        return parent::beginTransaction();
    }

    public function xcommit()
    {
        if (parent::inTransaction()) {
            $return = parent::commit();
            parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

            return $return;
        }

        throw new Exception\Transaction('commit, There is no active transaction', 50001);
    }

    public function xrollback()
    {
        if (parent::inTransaction()) {
            while (parent::inTransaction()) {
                if (false === parent::rollback()) {
                    return false;
                }
            }
            parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

            return true;
        }

        throw new Exception\Transaction('rollback, There is no active transaction', 50001);
    }

    /**
     * @throws \Exception
     */
    // public function transaction(\Closure $callback)
    // {
    //     if ($this->readonly) {
    //         throw new \Limepie\Exception('This is readonly mode: #4 ' . $this->getErrorMessage());
    //     }

    //     try {
    //         if ($this->xbegin()) {
    //             $callback = $callback->bindTo($this);
    //             $return   = $callback();

    //             if (!$return) {
    //                 throw new Exception\Transaction('Transaction Failure', 50003);
    //             }

    //             if ($this->xcommit()) {
    //                 return $return;
    //             }
    //         }

    //         throw new Exception\Transaction('Transaction Failure', 50005);
    //     } catch (\PDOException $e) {
    //         $this->xrollback();

    //         // 데드락에 의한 실패일 경우 한번더 실행
    //         if ($e->errorInfo && 40001 === $e->errorInfo[0]) {
    //             // 1초 지연
    //             $cho = 1000000;
    //             \usleep($cho / 2);

    //             if ($this->xbegin()) {
    //                 $callback = $callback->bindTo($this);
    //                 $return   = $callback();

    //                 if (!$return) {
    //                     throw new Exception\DeadLock('Deadlock Retry Failure', 50003);
    //                 }

    //                 if ($this->xcommit()) {
    //                     return $return;
    //                 }
    //             }
    //         }

    //         throw new Exception\DeadLock('Deadlock Retry Failure', 50005);
    //     } catch (\Throwable $e) {
    //         $this->xrollback();

    //         throw $e;
    //     }
    // }

    private function executeTransaction(\Closure $callback) : mixed
    {
        if ($this->xbegin()) {
            try {
                $callback = $callback->bindTo($this);
                $return   = $callback();

                if (!$return) {
                    throw new Exception\Transaction('Transaction callback returned false', 50003);
                }

                if ($this->xcommit()) {
                    return $return;
                }
            } catch (\Throwable $e) {
                $this->xrollback();

                throw $e;
            }
        }

        throw new Exception\Transaction('Failed to begin transaction', 50005);
    }

    private function isDeadlock(\PDOException $e) : bool
    {
        $status
            = '40001' == $e->getCode()
            || (isset($e->errorInfo[0]) && 40001 == $e->errorInfo[0])
            || '1213' == $e->getCode()
            || false !== \strpos($e->getMessage(), 'Deadlock');

        if ($this->isDeadlockLog) {
            try {
                \file_put_contents(
                    $this->deadlockLog,
                    PHP_EOL . PHP_EOL
                    . 'deadlock: ' . \date('Y-m-d H:i:s') . ' ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine(),
                    FILE_APPEND
                );
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $status;
    }

    public function transaction(\Closure $callback)
    {
        if ($this->readonly) {
            throw new \Limepie\Exception('This is readonly mode: #4 ' . $this->getErrorMessage());
        }

        $attempt    = 1;
        $maxRetries = 3;

        while ($attempt <= $maxRetries) {
            try {
                if ($this->isDeadlockLog) {
                    if ($attempt > 1) {
                        try {
                            \file_put_contents(
                                $this->deadlockLog,
                                PHP_EOL . PHP_EOL
                                . 'deadlock: ' . \date('Y-m-d H:i:s') . ' retry: ' . $attempt,
                                FILE_APPEND
                            );
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                return $this->executeTransaction($callback);
            } catch (\PDOException $e) {
                if ($this->isDeadlock($e)) {
                    if ($attempt < $maxRetries) {
                        $delay = $this->calculateDelay($attempt);

                        \usleep($delay);
                        ++$attempt;

                        continue; // 다음 시도
                    }

                    // 최대 재시도 횟수 초과 - 데드락 전용 예외
                    throw new Exception\Deadlock(
                        "Transaction failed after {$maxRetries} deadlock retries",
                        40001,
                        $maxRetries
                    );
                } else {
                    // 데드락이 아닌 PDO 에러
                    throw $e;
                    // throw new Exception\Transaction(
                    //     "Transaction PDO error on attempt {$attempt}",
                    //     50002,
                    // );
                }
            } catch (\Throwable $e) {
                // 일반 예외
                throw $e;
                // throw new Exception\Transaction(
                //     "Transaction general error on attempt {$attempt}",
                //     50001,
                // );
            }
        }
    }

    private function calculateDelay(int $attempt) : int
    {
        $baseDelay = 50000; // 50ms
        $backoff   = $baseDelay * (2 ** ($attempt - 1));
        $jitter    = \rand(0, $baseDelay);

        return \min($backoff + $jitter, 1000000); // 최대 1초
    }

    private function execute($statement, $bindParameters = [], $ret = false)
    {
        // pr($statement, $bindParameters);

        $stmt  = parent::prepare($statement);
        $binds = [];

        foreach ($bindParameters as $key => $value) {
            if (true === \is_array($value)) {
                foreach ($value as $r) {
                    if ($r instanceof ArrayObject) {
                        $binds[$key] = $r->attributes;
                    } else {
                        $binds[$key] = $r;
                    }

                    break;
                }
            } else {
                if ($value instanceof ArrayObject) {
                    $binds[$key] = $value->attributes;
                } else {
                    $binds[$key] = $value;
                }
            }
        }

        // pr($statement, $bindParameters);
        try {
            $result         = $stmt->execute($binds);
            $this->rowCount = $stmt->rowCount();

            if (true === $ret) {
                $stmt->closeCursor();

                return $result;
            }
        } catch (\Limepie\Exception $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
            // throw ($e)->setDebugMessage($stmt->errorInfo()[2]);
            // throw new \Limepie\Exception($e->getMessage(). ' ' .$stmt->errorInfo()[2]);
        } catch (\Throwable $e) {
            throw new Exception\Execute($e, $statement, $bindParameters);
            // throw (new \Limepie\Exception($e))->setDebugMessage($stmt->errorInfo()[2]);
            // throw new \Limepie\Exception($e->getMessage(). ' ' .$stmt->errorInfo()[2]);
        }

        return $stmt;
    }

    private function getMode($mode = null)
    {
        if (true === (null === $mode)) {
            $mode = $this->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
        }

        return $mode;
    }

    private function getErrorFormat($statement, array $binds = [])
    {
        $fixedBinds = [];

        foreach ($binds as $key => $value) {
            if (1 === \preg_match('#^:?(?P<type>gz|json|yaml|serialize|base64|aes)_#', $key, $typeMatch)) {
                $value = '[binary]';
            } elseif (1 === \preg_match('#^:?(?P<type>gz|json|yaml|serialize|base64|aes)$#', $key, $typeMatch)) {
                $value = '[hidden]';
            } elseif (1 === \preg_match('#aes#', $key, $typeMatch)) {
                $value = '[hidden]';
            }

            $fixedBinds[$key] = $value;
        }

        return \trim($statement) . ', [' . \Limepie\http_build_query($fixedBinds, '=', ', ') . ']';
    }

    private function getErrorMessage()
    {
        $message = '';

        foreach (\debug_backtrace() as $trace) {
            if (true === isset($trace['file'])) {
                if (
                    false === \strpos(
                        $trace['file'],
                        'yejune/limepie/src/Limepie'
                    )
                    // && !($trace['object'] instanceof Mysql)
                ) {
                    $message .= $trace['file'] . ' in line ' . $trace['line'];
                }
            }
        }

        return $message;
    }

    public function self()
    {
        return $this;
    }
}
