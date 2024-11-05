<?php declare(strict_types=1);

namespace Limepie;

use Doctrine\SqlFormatter\SqlFormatter;

class ModelBase extends ArrayObject
{
    public $pdo;

    public $dataStyles = [];

    public $dataTypes = [];

    public $tableName;

    public $newTableName;

    public $tableAliasName;

    public $primaryKeyName;

    public $sequenceName;

    public $primaryKeyValue; // insert update 판단

    public $normalColumns = [];

    public $timestampColumns = [];

    public $attributes = [];

    public $originAttributes = [];

    public $rawAttributes = [];

    public $plusAttributes = [];

    public $minusAttributes = [];

    public $selectColumns = ['*'];

    public $orderBy = '';

    public $keyName = '';

    public $valueName;

    public $offset;

    public $limit;

    public $query;

    public $binds = [];

    public $oneToOne = [];

    public $oneToMany = [];

    public $leftKeyName = '';

    public $rightKeyName = '';

    public $matchKeyRemove = true;

    public $and = [];

    public $condition = '';

    public $conditions = [];

    public $joinModels = [];

    public $bindcount = 0;

    public $secondKeyName;

    public $removeColumns = [];

    public $parent;

    public $forceIndexes = [];

    public $deleteLock = false;

    public $sumColumn = '';

    public $avgColumn = '';

    public $oneToOnes = [];

    public $oneToManies = [];

    public $allColumns = [];

    public static $debug = false;

    public $parentNode = false;

    public $parentNodeFlag = 0;

    public $callbackColumns = [];

    // update시 변경된 컬럼
    public $changeColumns = [];

    // update시 변경된 변수
    public $changeBinds = [];

    public $onCondition = '';

    public $onConditionBinds = [];

    public $sameColumns = []; // not used

    public $groupBy;

    public $groupKey;

    public function __construct(?\PDO $pdo = null, $attributes = null)
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }

        $this->keyName = $this->primaryKeyName;
    }

    public function __invoke(?\PDO $pdo = null, $attributes = null)
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }

        return $this;
    }

    public function __call(string $name, array $arguments = [])
    {
        //        prx($name, $arguments);

        if (0 === \strpos($name, 'groupBy')) {
            return $this->buildGroupBy($name, $arguments, 7);
        }

        if (0 === \strpos($name, 'orderBy')) {
            return $this->buildOrderBy($name, $arguments, 7);
        }

        if (0 === \strpos($name, 'where')) {
            return $this->buildCondition($name, $arguments, 5);
        }

        if (0 === \strpos($name, 'condition')) {
            return $this->buildCondition($name, $arguments, 9);
        }

        // if (0 === \strpos($name, 'where')) {
        //     return $this->buildWhere($name, $arguments, 5);
        // }

        if (0 === \strpos($name, 'and')) {
            return $this->buildAnd($name, $arguments, 3);
        }

        if (0 === \strpos($name, 'sum')) {
            return $this->buildSum($name, $arguments, 3);
        }

        if (0 === \strpos($name, 'avg')) {
            return $this->buildAvg($name, $arguments, 3);
        }

        if (0 === \strpos($name, 'or')) {
            return $this->buildOr($name, $arguments);
        }

        if (0 === \strpos($name, 'keyName')) {
            return $this->buildKeyName($name, $arguments);
        }

        if (0 === \strpos($name, 'alias')) {
            return $this->buildAlias($name, $arguments);
        }

        if (0 === \strpos($name, 'forceIndex')) {
            return $this->buildForceIndex($name, $arguments, 10);
        }

        // match하면서 addAllColumns 같이 하기
        if (0 === \strpos($name, 'matchAll')) {
            $this->addAllColumns();

            return $this->buildMatch($name, $arguments);
        }

        if (0 === \strpos($name, 'match')) {
            return $this->buildMatch($name, $arguments);
        }

        if (0 === \strpos($name, 'relations')) {
            return $this->buildRelation($name, $arguments, true);
        }

        if (0 === \strpos($name, 'relation')) {
            return $this->buildRelation($name, $arguments, false);
        }

        if (0 === \strpos($name, 'on')) {
            return $this->buildJoinOn($name, $arguments);
        }

        // if (0 === \strpos($name, 'joins')) {
        //     return $this->buildJoin($name, $arguments);
        // }

        if (0 === \strpos($name, 'join')) {
            return $this->buildJoin($name, $arguments);
        }

        // if (0 === \strpos($name, 'leftJoins')) {
        //     return $this->buildJoin($name, $arguments);
        // }

        if (0 === \strpos($name, 'leftJoin')) {
            return $this->buildJoin($name, $arguments);
        }

        if (0 === \strpos($name, 'getAllBy')) {
            $this->addAllColumns();

            return $this->buildGetBy($name, $arguments, 8);
        }

        if ('getAll' === $name) {
            $this->addAllColumns();

            return $this->get(...$arguments);
        }

        if (0 === \strpos($name, 'getBy')) {
            return $this->buildGetBy($name, $arguments, 5);
        }

        if (0 === \strpos($name, 'getsCount')) {
            return $this->buildCount($name, $arguments, 9, true);
        }

        if (0 === \strpos($name, 'getCountBy')) {
            return $this->buildCount($name, $arguments, 10);
        }

        if (0 === \strpos($name, 'getCount')) {
            return $this->buildCount($name, $arguments, 8);
        }

        if (0 === \strpos($name, 'getSum')) {
            return $this->buildGetSum($name, $arguments, 6);
        }

        if (0 === \strpos($name, 'getAvg')) {
            return $this->buildGetAvg($name, $arguments, 6);
        }

        if (0 === \strpos($name, 'getsAllBy')) {
            $this->addAllColumns();

            return $this->buildGetsBy($name, $arguments, 9);
        }

        if ('getsAll' === $name) {
            $this->addAllColumns();

            return $this->gets(...$arguments);
        }

        if (0 === \strpos($name, 'getsBy')) {
            return $this->buildGetsBy($name, $arguments, 6);
        }

        if (0 === \strpos($name, 'addColumn')) {
            return $this->buildAddColumn($name, $arguments);
        }

        if (0 === \strpos($name, 'removeColumn')) {
            return $this->buildRemoveColumn($name, $arguments);
        }

        if (0 === \strpos($name, 'setRaw')) {
            return $this->buildSetRaw($name, $arguments);
        }

        if (0 === \strpos($name, 'set')) {
            return $this->buildSet($name, $arguments);
        }

        if (0 === \strpos($name, 'newRaw')) {
            return $this->buildNewRaw($name, $arguments);
        }

        // attribute에 없는것을 처음 등록
        if (0 === \strpos($name, 'new')) {
            return $this->buildNew($name, $arguments);
        }

        if (0 === \strpos($name, 'plus')) {
            return $this->buildPlus($name, $arguments);
        }

        if (0 === \strpos($name, 'minus')) {
            return $this->buildMinus($name, $arguments);
        }

        if (0 === \strpos($name, 'get')) { // get column
            return $this->buildGetColumn($name, $arguments);
        }

        throw (new Exception('"' . $name . '" method not found', 404))
            ->setDisplayMessage('page not found', __FILE__, __LINE__)
        ;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)// : mixed
    {
        if (false === \array_key_exists($offset, $this->attributes)) {
            $traces = \debug_backtrace();

            foreach ($traces as $trace) {
                if (true === isset($trace['file'])) {
                    // if (false === \strpos($trace['file'], '/limepie-framework/src/')) {
                    // if($trace['function'] == '__call') continue;

                    if (false === \in_array($offset, $this->allColumns, true)) {
                        $message = 'Undefined offset: ' . $offset;
                        $code    = 400234;
                    } else {
                        $message = 'offset ' . $offset . ' is null';
                        $code    = 400123;
                    }
                    $filename = $trace['file'];
                    $line     = $trace['line'];

                    // $message = "{$code}: {$message} in <b>{$filename}</b> on line <b>{$line}</b>\n\n";

                    $e = (new Exception($message, $code))->setDebugMessage($message, __FILE__, __LINE__);

                    throw $e;

                    break;
                    // }
                }
            }
        }

        return $this->attributes[$offset];
    }

    public function setAttributes($attributes = null)
    {
        if ($attributes instanceof ArrayObject) {
            $attributes = $attributes->attributes;
        }

        if (\is_array($attributes)) {
            $type = 0;

            // 정해진 필드만
            if (1 === $type) {
                foreach ($this->allColumns as $column) {
                    if (true === isset($attributes[$column])) {
                        $this->attributes[$column] = $attributes[$column];
                    } elseif (true === isset($attributes[$this->tableName . '_' . $column])) {
                        $column1                   = $this->tableName . '_' . $column;
                        $this->attributes[$column] = $attributes[$column1];
                    } else {
                        $this->attributes[$column] = null;
                    }
                }
            } else {
                $this->attributes       = $this->buildDataType($attributes);
                $this->originAttributes = $this->attributes;

                // if ('quest_mission_type' == $this->tableName) {
                //     \prx($this->attributes, $this->originAttributes);

                //     exit;
                // }
            }
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;
        } else {
            $this->attributes = $attributes;
        }
    }

    public function getmicrotime()
    {
        [$usec, $sec] = \explode(' ', \microtime());

        return (float) $usec + (float) $sec;
    }

    public function buildDataType(array|string $attributes = [])
    {
        if (\is_array($attributes)) {
            foreach ($attributes as $column => &$value) {
                if (true === isset($this->dataStyles[$column])) {
                    switch ($this->dataStyles[$column]) {
                        case 'curlfile_serialize':
                            if ($value && \Limepie\is_serialized_string($value)) {
                                try {
                                    $value = CurlFile::unserialize($value);
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'serialize':
                            if ($value && \Limepie\is_serialized_string($value)) {
                                try {
                                    $value = \unserialize($value);
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'aes_serialize':
                            if ($value && \Limepie\is_serialized_string($value)) {
                                try {
                                    $value = \unserialize($value);
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'base64':
                            if ($value) {
                                $value = new ArrayObject(\unserialize(\base64_decode($value, true)));
                            } else {
                                $value = [];
                            }

                            break;
                        case 'gz':
                            if ($value) {
                                if (\Limepie\is_binary($value)) {
                                    $value = new ArrayObject(\unserialize(\gzuncompress($value)));
                                } else {
                                    $value = [];
                                }
                            } else {
                                $value = [];
                            }

                            break;
                            // case 'aes':
                            //     if ($value) {
                            //         if (\Limepie\is_binary($value)) {
                            //             try {
                            //                 $body = \Limepie\Aes::unpack($value);
                            //             } catch (\Exception) {
                            //                 $body = [];
                            //             }
                            //             $value = new \Limepie\ArrayObject($body);
                            //         } else {
                            //             $value = [];
                            //         }
                            //     } else {
                            //         $value = [];
                            //     }

                            //     break;
                        case 'jsons':
                            if ($value) {
                                $body = \json_decode($value, true);

                                if ($body) {
                                    $value = new ArrayObject($body);
                                } else {
                                    $value = [];
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'json':
                            if (false === \is_null($value)) {
                                $body = \json_decode($value, true);

                                if ($body) {
                                    $value = new ArrayObject($body);
                                } else {
                                    $value = [];
                                }
                            } else {
                                $value = null;
                            }

                            break;
                        case 'yml':
                        case 'yaml':
                            $body = \yaml_parse($value);

                            if ($body) {
                                $value = new ArrayObject($body);
                            } else {
                                $value = [];
                            }
                            // no break
                        case 'int':
                        case 'tinyint':
                            (int) $value;
                            // no break
                        case 'float':
                        case 'decimal':
                            (float) $value;

                            break;
                    }
                }
            }
        }

        return $attributes;
    }

    public static function newInstance(?\PDO $pdo = null, $attributes = null) : self
    {
        return new self($pdo, $attributes);
    }

    public function getGroupBy(?string $groupBy = null)
    {
        $sql = '';

        if (!$groupBy) {
            $groupBy = $this->groupBy;
        }

        if ($groupBy) {
            $sql .= \PHP_EOL . 'GROUP BY' . \PHP_EOL . '    ' . $groupBy;
        }

        return $sql;
    }

    public function groupBy(string $groupBy, $groupKey) : self
    {
        $this->groupBy  = $groupBy;
        $this->groupKey = $groupKey;

        return $this;
    }

    public function getOrderBy(?string $orderBy = null)
    {
        $sql = '';

        if (!$orderBy) {
            $orderBy = $this->orderBy;
        }

        if ($orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $orderBy;
        }

        return $sql;
    }

    public function orderBy(string $orderBy) : self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function addColumns(array $columns) : self
    {
        $this->selectColumns = \array_merge($this->selectColumns, $columns);

        return $this;
    }

    public $rawColumnString = '';

    public function addRawColumn($rawColumnString) : self
    {
        $this->rawColumnString = $rawColumnString;

        return $this;
    }

    public function removeColumn($column) : self
    {
        $this->removeColumns[] = $column;

        return $this;
    }

    public function removeColumns(array $columns) : self
    {
        $this->removeColumns = $columns;

        return $this;
    }

    public $isRemoveAllColumn = false;

    public function removeAllColumns() : self
    {
        $this->selectColumns     = $this->fkColumns;
        $this->selectColumns[]   = $this->primaryKeyName;
        $this->isRemoveAllColumn = true;

        return $this;
    }

    public function onlyColumns(array $columns) : self
    {
        $this->selectColumns   = $this->fkColumns;
        $this->selectColumns[] = $this->primaryKeyName;
        $this->selectColumns   = \array_merge($this->selectColumns, $columns);

        return $this;
    }

    public function addAllColumns() : self
    {
        $this->selectColumns = $this->allColumns;

        return $this;
    }

    public function fetchKey(callable $callback) : self
    {
        $this->keyName = $callback;

        return $this;
    }

    public function fetchValue(callable $callback) : self
    {
        $this->valueName = $callback;

        return $this;
    }

    public function keyName(callable|string $keyName, ?string $secondKeyName = null) : self
    {
        $this->keyName = $keyName;

        $this->secondKeyName = $secondKeyName;

        return $this;
    }

    protected function buildJoin(string $name, array $arguments) : self
    {
        /*
            $joinModels = (new SangpumDeal($slave1))
                ->joinSeqWithSangpumDealSeq(
                    (new SangpumDealItem)
                        ->andIsClose(0)
                        ->addColumnSeqAliasAaaa()
                        ->addColumnIsCloseAliasBbbb('LENGTH(INSTR(%s, 1))')
                )
                ->leftJoinSangpumDealSeqWithSangpumDealSeq(new SangpumDealItemExtend)
                ->getsByIsSale(1)
            ;


            $joinModels = (new SangpumDeal($slave1))
                ->joinSeqWithSangpumDealSeq(
                    (new SangpumDealItem)
                        ->andIsClose(0)
                        ->addColumnSeqAliasAaaa()
                        ->addColumnIsCloseAliasBbbb('LENGTH(INSTR(%s, 1))')
                )
                ->leftJoinSangpumDealSeqWithSangpumDealSeq(new SangpumDealItemExtend)

                ->getsByIsSale(1)
            ;

            // source model을 바꾸는 경우
            $joinModels = (new SangpumDeal($slave1))
                ->joinSangpumSeqWithSeq(
                    $sangpumModel = (new Sangpum())
                        ->andIsSale(1)
                        ->aliasSangpum()
                )
                ->leftJoinSangpumSeqWithSeq(
                    (new SangpumType)
                    , $sangpumModel
                )
                ->getsByIsSaleAndLtSaleStartDtAndGtSaleEndDt(1, \date('Y-m-d H:i:s'), \date('Y-m-d H:i:s'))
            ;
        */

        if (1 === \preg_match('#(?P<type>left)?(j|J)oin(?P<multi>s)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)#', $name, $m)) {
            $this->joinModels[] = [
                'model'  => $arguments[0],
                'left'   => \Limepie\decamelize($m['leftKeyName']),
                'right'  => \Limepie\decamelize($m['rightKeyName']),
                'type'   => $m['type'] ? true : false,
                'target' => $arguments[1] ?? null,
                'multi'  => $m['multi'] ? true : false,
            ];
        } else {
            throw new Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    public function alias(string $tableName) : self
    {
        $this->newTableName   = $tableName;
        $this->tableAliasName = $tableName;

        return $this;
    }

    public function print(?string $sql = null, ?array $binds = null) : void
    {
        if (false === isset($GLOBALS['queryCount'])) {
            $GLOBALS['queryCount'] = 0;
        }
        ++$GLOBALS['queryCount'];

        if (!$sql) {
            $sql = $this->query;
        }

        if (!$binds) {
            $binds = $this->binds;
        }
        Timer::start();
        $data  = $this->getConnect()->gets('EXPLAIN ' . $sql, $binds);
        $timer = Timer::stop();
        echo '<br /><br /><table class="model-debug">';

        foreach (\debug_backtrace() as $trace) {
            if (true === isset($trace['file'])) {
                if (false === \strpos($trace['file'], 'yejune/limepie/src/Limepie')) {
                    $filename = $trace['file'];
                    $line     = $trace['line'];

                    echo '<tr><th>(' . $GLOBALS['queryCount'] . ') file ' . $filename . ' on line ' . $line . ($timer ? ', explain timer (' . $timer . ')' : '') . '</th></tr>';

                    break;
                }
            }
        }
        echo '<tr><td>';
        echo (new SqlFormatter())->format($this->replaceQueryBinds($sql, $binds)); // , $binds);

        if ($data && isset($data[0])) {
            echo '<style>
            .model-debug thead, .model-debug tbody, .model-debug tfoot, .model-debug tr, .model-debug td, .model-debug th {
                border:1px solid gray;
                font-size: 9pt;
            }
            .model-debug th {
                background: gray;
                color: white;
                border-color: white;
            }
            .model-debug td, .model-debug th {
                padding:5px;
            }
            </style>
            <table class="model-debug" border=1 cellpadding=1 cellspacing=1>';
            echo '<tr>';

            foreach ($data[0] as $key => $column) {
                echo '<th>';
                echo $key;
                echo '</th>';
            }
            echo '</tr>';

            foreach ($data as $index => $row) {
                echo '<tr>';

                foreach ($row as $key => $column) {
                    echo '<td>';
                    echo $column;
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</td></tr></table>';
        echo 'request time : ' . (\microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . '<br />';
        // exit;
    }

    // getBy, getsBy, getCountBy 구문 뒤의 구문을 분석하여 조건문을 만든다.
    public function getConditionAndBinds(string $whereKey, array $arguments = [], int $offset = 0) : array
    {
        $condition = '';
        $binds     = [];
        $conds     = [];

        [$conds, $binds] = $this->getConditions($whereKey, $arguments, $offset);
        $condition       = \trim(\implode(PHP_EOL . '        ', $conds));

        return [$condition, $binds];
    }

    public function match(string $leftKeyName, string $rightKeyName) : Model
    {
        $this->leftKeyName  = $leftKeyName;
        $this->rightKeyName = $rightKeyName;

        return $this;
    }

    public function relation($class)
    {
        return $this->oneToOne($class);
    }

    public function relations($class)
    {
        return $this->oneToMany($class);
    }

    public function oneToOne($class)
    {
        $this->oneToOne[] = $class;

        return $this;
    }

    public function oneToMany($class)
    {
        $this->oneToMany[] = $class;

        return $this;
    }

    public function limit(int $offset, int $limit)
    {
        $this->offset = $offset;
        $this->limit  = $limit;

        return $this;
    }

    public function getLimit()
    {
        return $this->limit ? ' LIMIT ' . $this->offset . ', ' . $this->limit : '';
    }

    public function getConnect()
    {
        if (!$this->pdo) {
            throw new Exception($this->tableName . ' db connection not found');
        }

        return $this->pdo;
    }

    public function setConnect(\PDO $connect)
    {
        return $this->pdo = $connect;
    }

    public function filter(?\Closure $callback = null)
    {
        if (true === isset($callback) && $callback) {
            return $callback($this);
        }
    }

    #[\ReturnTypeWillChange]
    public function key(?string $keyName = null)// : mixed
    {
        if ($keyName) {
            return $this->keyName($keyName);
        }

        return \key($this->attributes);
    }

    public function save()
    {
        if (0 < \strlen((string) $this->primaryKeyValue)) {
            return $this->update();
        }

        return $this->create();
    }

    public function parentNode($flag = 0)
    {
        $this->parentNode     = true;
        $this->parentNodeFlag = $flag;

        return $this;
    }

    protected function buildForceIndex(string $name, array $arguments, int $offset = 3) : self
    {
        $key = \strtolower(\substr($name, $offset));

        return $this->forceIndex($key);
    }

    public function forceIndex(string $indexKey) : self
    {
        $this->forceIndexes[] = ' FORCE INDEX (`' . $indexKey . '`)';

        return $this;
    }

    protected function buildNew(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        $this->attributes[$columnName] = $arguments[0];

        return $this;
    }

    // $model->setRawLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
    protected function buildNewRaw(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 6));

        $this->rawAttributes[$columnName] = $arguments[0];
        $this->attributes[$columnName]    = $arguments[1] ?? null;

        return $this;
    }

    protected function buildPlus(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 4));

        $this->attributes[$columnName]     = ($this->attributes[$columnName] ?? 0) + $arguments[0];
        $this->plusAttributes[$columnName] = $arguments[0];

        return $this;
    }

    protected function buildMinus(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 5));

        $this->attributes[$columnName]      = ($this->attributes[$columnName] ?? 0) - $arguments[0];
        $this->minusAttributes[$columnName] = $arguments[0];

        return $this;
    }

    protected function buildWhere(string $name, array $arguments, int $offset = 5)
    {
        [$this->condition, $this->binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        return $this;
    }

    protected function buildSum(string $name, array $arguments, int $offset = 3) : self
    {
        $this->sumColumn = '`' . $this->tableAliasName . '`.`' . \Limepie\decamelize(\substr($name, $offset)) . '`';

        return $this;
    }

    protected function buildAvg(string $name, array $arguments, int $offset = 3) : self
    {
        $this->avgColumn = '`' . $this->tableAliasName . '`.`' . \Limepie\decamelize(\substr($name, $offset)) . '`';

        return $this;
    }

    public function openParenthesis() : self
    {
        $this->condition .= ' ( ';

        return $this;
    }

    public function closeParenthesis() : self
    {
        $this->condition .= ' ) ';

        return $this;
    }

    public function where() : self
    {
        $this->condition .= '';

        return $this;
    }

    public function helper() : self
    {
        $conditions = [
            'gt(greater then, > :)',
            'lt(less then, < :)',
            'ge(greater equal, >= :)',
            'le(less equal, <= :)',
            'eq(equal, = :)',
            'ne(not equal, != :)',
            'lk(like, LIKE :)',
        ];

        throw new Exception(\limepie\http_build_query($conditions, ': ', ', ', encode: true) . ' condition string is empty');
    }

    public function condition(string $string) : self
    {
        $this->condition .= ' ' . $string;

        return $this;
    }

    protected function empty()
    {
        return null;
    }

    public function deleteLock($flag = true) : self
    {
        $this->deleteLock = $flag;

        return $this;
    }

    public function getDeleteLock()
    {
        return $this->deleteLock;
    }

    public static function debug(
        ?string $filename = null,
        null|int|string $line = null
    ) {
        static::$debug = true;

        if (!$filename) {
            $trace    = \debug_backtrace()[0];
            $filename = $trace['file'];
            $line     = $trace['line'];
        }
        echo 'debug on: file ' . $filename . ' on line ' . $line . '<br />';
    }

    protected function buildAlias(string $name, array $arguments) : self
    {
        $this->newTableName = \Limepie\decamelize(\substr($name, 5));
        // $this->tableAliasName = \Limepie\decamelize(\substr($name, 5));

        return $this;
    }

    public function column($columnName, $alias, $callback)
    {
        $this->callbackColumns[] = [
            'column'   => $columnName,
            'alias'    => $alias,
            'callback' => $callback,
        ];

        return $this;
    }

    /**
     * $joinModels->{'getsByServiceSeqAnd((IsSaleAndLtSaleStartDtAndGtSaleEndDt)Or(IsSale))'}(
     * $joinModels->{'conditionServiceSeqAnd((IsSaleAndLtSaleStartDtAndGtSaleEndDt)Or(IsSale))'}(
     * $serviceSeq,
     * 1,
     * \date('Y-m-d H:i:s'),
     * \date('Y-m-d H:i:s'),
     * 2
     * )
     * $joinModels
     * ->conditionServiceSeq($serviceSeq)
     * ->{'and('}()
     * ->{'condition(IsSaleAndLtSaleStartDtAndGtSaleEndDt)'}(1, \date('Y-m-d H:i:s'), \date('Y-m-d H:i:s'))
     * ->{'or(IsSale)'}(2)
     * ->{'condition)'}()
     * ->gets()
     * ;.
     */
    protected function buildCondition(string $name, array $arguments = [], int $offset = 9) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true) && false === isset($arguments[0])) {
            $this->condition .= $operator;
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);

            $this->condition .= \implode(' ', $conds);
            $this->binds += $binds;
        }

        return $this;
    }

    protected function buildJoinOn(string $name, array $arguments = [], int $offset = 2) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true) && false === isset($arguments[0])) {
            $this->onCondition .= $operator;
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);

            $this->onCondition .= \implode(' ', $conds);
            $this->onConditionBinds += $binds;
        }

        return $this;
    }

    public function and(?string $key = null, $value = null) : self
    {
        if (null === $key) {
            $this->condition .= ' AND ';

            return $this;
        }

        return $this->buildAnd($key, [$value], 0);
    }

    public function or(?string $key = null, $value = null) : self
    {
        if (null === $key) {
            $this->condition .= ' OR ';

            return $this;
        }

        return $this->buildOr($key, [$value], 0);
    }

    public function open() : self
    {
        $this->conditions[] = ['string' => '('];

        return $this;
    }

    public function close() : self
    {
        $this->conditions[] = ['string' => ')'];

        return $this;
    }

    protected function buildKeyName(string $name, array $arguments) : self
    {
        if (1 === \preg_match('#keyName(?P<leftKeyName>.*)(With(?P<rightKeyName>.*))?$#U', $name, $m)) {
            $this->keyName = \Limepie\decamelize($m['leftKeyName']);

            if (true === isset($m['rightKeyName'])) {
                $this->secondKeyName = \Limepie\decamelize($m['rightKeyName']);
            }
        } else {
            $this->keyName = \Limepie\decamelize(\substr($name, 7));
        }
        // \prx($this->tableName, $name, $this->keyName);

        //    \pr($this->keyName);

        return $this;
    }

    /**
     * @example
     *     $userModels = (new UserModel)($slave1)
     *         ->addColumn('seq', 'cash', '(SELECT COALESCE(SUM(amount), 0) FROM point WHERE to_user_seq = %s AND status = 1 AND expired_ts > now())')
     */
    public function addColumn(string $columnName, ?string $aliasName = null, ?string $format = null)
    {
        if (null === $aliasName) { // alias name이 없으면 index로 넣음
            $this->selectColumns[] = $columnName;
        } else {
            $this->selectColumns[$aliasName] = new class ($columnName, $aliasName, $format) {
                public $columnName;

                public $aliasName;

                public $format;

                public function __construct(string $columnName, ?string $aliasName = null, ?string $format = null)
                {
                    $this->columnName = $columnName;
                    $this->aliasName  = $aliasName;
                    $this->format     = $format;
                }
            };
        }

        return $this;
    }

    public function buildRemoveColumn($name, $arguments = [])
    {
        $columnName = \Limepie\decamelize(\substr($name, 12));

        $this->removeColumns[] = $columnName;

        return $this;
    }

    protected function buildSet(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        if (false === \array_key_exists(0, $arguments)) {
            throw new Exception($columnName . ' not found.');
        }

        // setvalue에 의해 attributes가 배열이 아닌 경우가 있음. set하는 경우 origin으로 복원
        if (false === \is_array($this->attributes)) {
            $this->attributes = $this->originAttributes;
        }

        $this->attributes[$columnName] = $arguments[0];

        return $this;
    }

    // $model->setRawLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
    protected function buildSetRaw(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 6));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        $this->rawAttributes[$columnName] = $arguments[0];
        $this->attributes[$columnName]    = $arguments[1] ?? null;

        return $this;
    }

    public function delete(bool $recursive = false)
    {
        if ($recursive) {
            return $this->objectToDelete();
        }

        return $this->doDelete();
    }

    protected function iteratorToDelete(array|self $attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if ($attribute instanceof self) {
                if (false === $attribute->getDeleteLock()) {
                    $attribute($this->getConnect())->objectToDelete();
                }
            } else {
                if (true === \is_array($attribute)) {
                    if (0 < \count($attribute)) {
                        foreach ($attribute as $k2 => $v2) {
                            if ($v2 instanceof self) {
                                if (false === $v2->getDeleteLock()) {
                                    $v2($this->getConnect())->objectToDelete();
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function objectToDelete() : bool
    {
        if (true === isset($this->attributes[$this->primaryKeyName])) { // 단일 row
            $this->iteratorToDelete($this->attributes);

            if (false === $this->getDeleteLock()) {
                $this->doDelete();
            }

            return true;
        }

        foreach ($this->attributes as $index => $attribute) { // multi rows
            if (true === isset($attribute[$attribute->primaryKeyName])) {
                $this->iteratorToDelete($attribute);

                if (false === $this->getDeleteLock()) {
                    $attribute($this->getConnect())->doDelete();
                    unset($this->attributes[$index]);
                }
            }
        }

        return true;
    }

    /**
     * Escape binds of a SQL query.
     *
     * @param mixed $parameter
     */
    protected static function escapeFunction($parameter) : string
    {
        $result = $parameter;

        switch (true) {
            // Check if result is non-unicode string using PCRE_UTF8 modifier
            case \is_string($result) && !\preg_match('//u', $result):
                $result = '0x' . \strtoupper(\bin2hex($result));

                break;
            case \is_string($result):
                $result = "'" . \addslashes($result) . "'";

                break;
            case null === $result:
                $result = 'NULL';

                break;
            case \is_bool($result):
                $result = $result ? '1' : '0';

                break;

            default:
                $result = (string) $result;
        }

        return $result;
    }

    /**
     * Return a query with the binds replaced.
     */
    public function replaceQueryBinds(string $query, array $binds) : string
    {
        return \preg_replace_callback(
            '/\?|((?<!:):([a-z][a-z0-9_]+))/i',
            static function ($matches) use ($binds) {
                $value = null;

                // \pr($query, $matches);
                if (false === isset($matches[2])) {
                    return $matches[0];
                }

                if (0 === \strpos($matches[2], 'aes_') || false !== \strpos($matches[2], '_aes_')) {
                    return '[hidden]';
                }

                if (true === isset($binds[':' . $matches[2]])) {
                    $value = $binds[':' . $matches[2]];
                } else {
                    $value = $binds[$matches[2]] ?? null;
                }

                if (true === \is_numeric($value)) {
                    if (true === \is_string($value) && '0' == $value[0]) {
                    } else {
                        return $value;
                    }
                }

                return static::escapeFunction($value);
            },
            $query
        );
    }

    /**
     * @example
     *     $userModels = (new UserModel)($slave1)
     *         ->addColumnSeqAliasTotalPoint('(SELECT COALESCE(SUM(amount), 0) FROM point WHERE to_user_seq = %s AND expired_ts > now())')
     */
    public function buildAddColumn(string $name, array $arguments = []) : self
    {
        $aliasName = null;

        if (1 === \preg_match('#addColumn(?P<leftKeyName>.*)(Alias(?P<rightKeyName>.*))?$#U', $name, $m)) {
            $columnName = \Limepie\decamelize($m['leftKeyName']);

            if (true === isset($m['rightKeyName'])) {
                $aliasName = \Limepie\decamelize($m['rightKeyName']);
            }
        } else {
            $columnName = \Limepie\decamelize(\substr($name, 9));
        }

        if ($aliasName) {
            if (true === isset($arguments[0])) {
                $this->addColumn($columnName, $aliasName, $arguments[0]);
            } else {
                $this->addColumn($columnName, $aliasName);
            }
        } else {
            $this->addColumn($columnName);
        }

        return $this;
    }

    protected function buildMatch(string $name, $arguments) : self
    {
        if (true === isset($arguments[0])) {
            $this->matchKeyRemove = $arguments[0];
        }

        if (1 === \preg_match('#match(All)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)$#U', $name, $m)) {
            $this->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $this->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    protected function buildRelation(string $name, array $arguments = [], $isMulti = false) : self
    {
        $class = $arguments[0];

        if (1 === \preg_match('#relation(s)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)$#U', $name, $m)) {
            $class->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $class->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new Exception('"' . $name . '" syntax error', 1999);
        }

        if ($isMulti) {
            $this->oneToMany($class);
        } else {
            $this->oneToOne($class);
        }

        return $this;
    }

    public static function function($bind, $extraCondition, $extraBinds = [])
    {
        /*
            $sangjumDomainModels = (new SangjumDomainModel)($slave1)
                ->getsAllByServiceSeqAndLeLocation(
                    $serviceSeq
                    , \Limepie\Model::function(
                        2000
                        , 'ST_Distance_Sphere(%s, ST_GeomFromText(:location))'
                        , [
                            ':location' => 'POINT(129.1683702240102 35.16102579864174)',
                        ]
                    )
                )
            ;
        */
        return new class ($bind, $extraCondition, $extraBinds) {
            public $extraCondition;

            public $bind;

            public $extraBinds = [];

            public function __construct($bind, $extraCondition, $extraBinds = [])
            {
                $this->extraCondition = $extraCondition;
                $this->bind           = $bind;
                $this->extraBinds     = $extraBinds;
            }
        };
    }
}
