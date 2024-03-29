<?php declare(strict_types=1);

namespace Limepie;

class ModelOld extends ArrayObject
{
    public $pdo;

    public $dataStyles = [];

    public $dataTypes = [];

    public $tableName;

    public $newTableName;

    public $tableAliasName;

    public $primaryKeyName;

    public $sequenceName;

    public $primaryKeyValue;

    public $normalColumns = [];

    public $timestampColumns = [];

    public $attributes = [];

    public $rawAttributes = [];

    public $selectColumns = ['*'];

    public $orderBy = '';

    public $keyName = '';

    public $offset;

    public $limit;

    public $query;

    public $binds = [];

    public $oneToOne = [];

    public $oneToMany = [];

    public $leftKeyName = '';

    public $rightKeyName = '';

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

    public $oneToOnes = [];

    public $oneToManys = [];

    public static $debug = false;

    public static function newInstance(\Pdo $pdo = null, array $attributes = []) : self
    {
        return new self($pdo, $attributes);
    }

    public function __construct(\Pdo $pdo = null, array $attributes = [])
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }
        $this->keyName = $this->primaryKeyName;
    }

    public function __invoke(\Pdo $pdo = null)
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        return $this;
    }

    public function __call(string $name, array $arguments = [])
    {
        if (0 === \strpos($name, 'orderBy')) {
            return $this->buildOrderBy($name, $arguments, 7);
        }

        if (0 === \strpos($name, 'condition')) {
            return $this->buildCondition($name, $arguments, 9);
        }

        if (0 === \strpos($name, 'where')) {
            return $this->buildWhere($name, $arguments, 5);
        }

        if (0 === \strpos($name, 'and')) {
            return $this->buildAnd($name, $arguments, 3);
        }

        if (0 === \strpos($name, 'sum')) {
            return $this->buildSum($name, $arguments, 3);
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

        if (0 === \strpos($name, 'matchAll')) {
            $this->getAllColumns();

            return $this->buildMatch($name, $arguments);
        }

        if (0 === \strpos($name, 'match')) {
            return $this->buildMatch($name, $arguments);
        }

        if (0 === \strpos($name, 'join')) {
            return $this->buildJoin($name, $arguments);
        }

        if (0 === \strpos($name, 'leftJoin')) {
            return $this->buildJoin($name, $arguments);
        }

        if (0 === \strpos($name, 'getAllBy')) {
            $this->getAllColumns();

            return $this->buildGetBy($name, $arguments, 8);
        }

        if (0 === \strpos($name, 'getBy')) {
            return $this->buildGetBy($name, $arguments, 5);
        }

        if (0 === \strpos($name, 'getCount')) {
            return $this->buildGetCount($name, $arguments, 8);
        }

        if (0 === \strpos($name, 'getSum')) {
            return $this->buildGetSum($name, $arguments, 6);
        }

        if (0 === \strpos($name, 'getsAllBy')) {
            $this->getAllColumns();

            return $this->buildGetsBy($name, $arguments, 9);
        }

        if (0 === \strpos($name, 'getsBy')) {
            return $this->buildGetsBy($name, $arguments, 6);
        }

        if (0 === \strpos($name, 'addColumn')) {
            return $this->buildAddColumn($name, $arguments);
        }

        if (0 === \strpos($name, 'setRaw')) {
            return $this->buildSetRaw($name, $arguments);
        }

        if (0 === \strpos($name, 'set')) {
            return $this->buildSet($name, $arguments);
        }

        if (0 === \strpos($name, 'get')) { // get column
            return $this->buildGetColumn($name, $arguments);
        }

        throw new \Limepie\Exception('"' . $name . '" method not found', 1999);
    }

    public function offsetGet($offset)
    {
        if (false === \array_key_exists($offset, $this->attributes)) {
            $traces = \debug_backtrace();

            foreach ($traces as $trace) {
                if (true === isset($trace['file'])) {
                    if (false === \strpos($trace['file'], '/limepie-framework/src/')) {
                        //if($trace['function'] == '__call') continue;

                        if (false === \in_array($offset, $this->allColumns, true)) {
                            $message = 'Undefined offset: ' . $offset;
                            $code    = 234;
                        } else {
                            $message = 'offset ' . $offset . ' is null';
                            $code    = 123;
                        }
                        $filename = $trace['file'];
                        $line     = $trace['line'];

                        // $message = "{$code}: {$message} in <b>{$filename}</b> on line <b>{$line}</b>\n\n";

                        $e = (new \Limepie\Exception($message, $code)); //->setFile($filename)->setLine($line)->setCode($code);

                        throw $e;

                        break;
                    }
                }
            }
        }

        return $this->attributes[$offset];
    }

    public function setAttribute(string $column, array $attribute = [])
    {
        $this->attributes[$column] = $attribute;
    }

    public function setAttributeses(array | \Limepie\ArrayObject $attributes = [])
    {
        $class = \get_called_class();

        if ($attributes instanceof \Limepie\ArrayObject) {
            $attributes = $attributes->attributes;
        }

        foreach ($attributes as $attribute) {
            $this->attributes[] = new $class($this->pdo, $attribute);
        }

        return $this;
    }

    public function setAttributes(array | \Limepie\ArrayObject $attributes = [])
    {
        if ($attributes instanceof \Limepie\ArrayObject) {
            $attributes = $attributes->attributes;
        }

        if ($attributes) {
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
                $this->attributes = $this->buildDataType($attributes);
            }
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;
        }
    }

    public function getmicrotime()
    {
        [$usec, $sec] = \explode(' ', \microtime());

        return (float) $usec + (float) $sec;
    }

    public function buildDataType(array $attributes = [])
    {
        if ($attributes) {
            foreach ($attributes as $column => &$value) {
                if (true === isset($this->dataStyles[$column])) {
                    switch ($this->dataStyles[$column]) {
                        case 'serialize':
                            if ($value) {
                                try {
                                    $value = new \Limepie\ArrayObject(\unserialize($value));
                                } catch (\Exception $e) {
                                    throw $e;
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'base64':
                            if ($value) {
                                $value = new \Limepie\ArrayObject(\unserialize(\base64_decode($value, true)));
                            } else {
                                $value = [];
                            }

                            break;
                        case 'gz':
                            if ($value) {
                                if (\Limepie\is_binary($value)) {
                                    $value = new \Limepie\ArrayObject(\unserialize(\gzuncompress($value)));
                                } else {
                                    $value = [];
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'json':
                            if ($value) {
                                $value = new \Limepie\ArrayObject(\json_decode($value, true));
                            } else {
                                $value = [];
                            }

                            break;
                        case 'yml':
                        case 'yaml':
                            if ($value) {
                                $value = new \Limepie\ArrayObject(\yaml_parse($value));
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

            return $attributes;
        }
    }

    public function getRelation(array $attributes = [])
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                if ($class->leftKeyName) {
                    $leftKeyName = $class->leftKeyName;
                } else {
                    $leftKeyName = $class->primaryKeyName;
                }

                if ($class->rightKeyName) {
                    $rightKeyName = $class->rightKeyName;
                } else {
                    $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                }

                $functionName = 'getBy' . \Limepie\camelize($rightKeyName);

                if (false === \array_key_exists($leftKeyName, $attributes)) {
                    throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                }

                $args = [$attributes[$leftKeyName]];

                foreach ($class->and as $key => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key);
                    $args[] = $value;
                }

                $class->keyName = $rightKeyName;

                $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                if ($data) {
                    $data->deleteLock = $class->deleteLock;
                }

                if ($class->newTableName) {
                    $attributes[$class->newTableName] = $data;
                } else {
                    $attributes[$class->tableName . '_model'] = $data;
                }
            }
        }

        if ($this->oneToOnes ?? false) {
            foreach ($this->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    if ($class->leftKeyName) {
                        $leftKeyName = $class->leftKeyName;
                    } else {
                        $leftKeyName = $class->primaryKeyName;
                    }

                    if ($class->rightKeyName) {
                        $rightKeyName = $class->rightKeyName;
                    } else {
                        $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                    }

                    $functionName = 'getBy' . \Limepie\camelize($rightKeyName);

                    if (false === \array_key_exists($leftKeyName, $attributes[$parentTableName]->toArray())) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$parentTableName][$leftKeyName]];

                    foreach ($class->and as $key => $value) {
                        $functionName .= 'And' . \Limepie\camelize($key);
                        $args[] = $value;
                    }

                    $class->keyName = $rightKeyName;

                    $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                    if ($data) {
                        $data->deleteLock = $class->deleteLock;
                    }

                    if ($class->newTableName) {
                        $attributes[$parentTableName][$class->newTableName] = $data;
                    } else {
                        $attributes[$parentTableName][$class->tableName . '_model'] = $data;
                    }
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                if ($class->leftKeyName) {
                    $leftKeyName = $class->leftKeyName;
                } else {
                    $leftKeyName = $class->primaryKeyName;
                }

                if ($class->rightKeyName) {
                    $rightKeyName = $class->rightKeyName;
                } else {
                    $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                }

                $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);

                if (false === \array_key_exists($leftKeyName, $attributes)) {
                    throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                }

                $args = [$attributes[$leftKeyName]];

                foreach ($class->and as $key1 => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key1);
                    $args[] = $value;
                }

                $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                if ($data) {
                    $data->deleteLock = $class->deleteLock;
                }

                if ($class->newTableName) {
                    $attributes[$class->newTableName] = $data;
                } else {
                    $attributes[$class->tableName . '_models'] = $data;
                }
            }
        }

        if ($this->oneToManys ?? false) {
            foreach ($this->oneToManys as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    if ($class->leftKeyName) {
                        $leftKeyName = $class->leftKeyName;
                    } else {
                        $leftKeyName = $class->primaryKeyName;
                    }

                    if ($class->rightKeyName) {
                        $rightKeyName = $class->rightKeyName;
                    } else {
                        $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                    }

                    $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);

                    if (false === \array_key_exists($leftKeyName, $attributes[$parentTableName])) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$parentTableName][$leftKeyName]];

                    foreach ($class->and as $key1 => $value) {
                        $functionName .= 'And' . \Limepie\camelize($key1);
                        $args[] = $value;
                    }

                    $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                    if ($data) {
                        $data->deleteLock = $class->deleteLock;
                    }

                    if ($class->newTableName) {
                        $attributes[$parentTableName][$class->newTableName] = $data;
                    } else {
                        $attributes[$parentTableName][$class->tableName . '_models'] = $data;
                    }
                }
            }
        }

        return $attributes;
    }

    public function getRelations(array $attributes = [])
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                if ($class->newTableName) {
                    $moduleName = $class->newTableName;
                } else {
                    $moduleName = $class->tableName . '_model';
                }

                if ($class->leftKeyName) {
                    $leftKeyName = $class->leftKeyName;
                } else {
                    $leftKeyName = $class->primaryKeyName;
                }

                if ($class->rightKeyName) {
                    $rightKeyName = $class->rightKeyName;
                } else {
                    $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                }

                $seqs = [];

                foreach ($attributes as $row) {
                    if (true === \array_key_exists($leftKeyName, $row->toArray())) {
                        if (null !== $row[$leftKeyName]) {
                            $seqs[] = $row[$leftKeyName];
                        }
                    } else {
                        throw new \Limepie\Exception($this->tableName . ' table ' . $leftKeyName . ' column not found #2');
                    }
                }

                if ($seqs) {
                    $seqs         = \array_unique($seqs);
                    $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);
                    $args         = [$seqs];

                    foreach ($class->and as $key => $value) {
                        $functionName .= 'And' . \Limepie\camelize($key);
                        $args[] = $value;
                    }

                    $class->keyName = $rightKeyName;

                    $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                    if ($data) {
                        foreach ($attributes as $attribute) {
                            $attr = $attribute[$leftKeyName] ?? false;

                            if ($attr && true === isset($data[$attr])) {
                                if ($data[$attr]) {
                                    $data[$attr]->deleteLock = $class->deleteLock;
                                }
                                $attribute->offsetSet($moduleName, $data[$attr]);
                            } else {
                                $attribute->offsetSet($moduleName, null);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, null);
                        }
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        $attribute->offsetSet($moduleName, null);
                    }
                }
            }
        }

        if ($this->oneToOnes ?? false) {
            foreach ($this->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    if ($class->newTableName) {
                        $moduleName = $class->newTableName;
                    } else {
                        $moduleName = $class->tableName . '_model';
                    }

                    if ($class->leftKeyName) {
                        $leftKeyName = $class->leftKeyName;
                    } else {
                        $leftKeyName = $class->primaryKeyName;
                    }

                    if ($class->rightKeyName) {
                        $rightKeyName = $class->rightKeyName;
                    } else {
                        $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                    }

                    $seqs = [];

                    foreach ($attributes as $row) {
                        if (true === \array_key_exists($leftKeyName, $row[$parentTableName]->toArray())) {
                            if (null !== $row[$parentTableName][$leftKeyName]) {
                                $seqs[] = $row[$parentTableName][$leftKeyName];
                            }
                        } else {
                            throw new \Limepie\Exception($this->tableName . ' table ' . $leftKeyName . ' column not found #1');
                        }
                    }

                    if ($seqs) {
                        $seqs = \array_unique($seqs);

                        $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);
                        $args         = [$seqs];

                        foreach ($class->and as $key => $value) {
                            $functionName .= 'And' . \Limepie\camelize($key);
                            $args[] = $value;
                        }

                        $class->keyName = $rightKeyName;

                        $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                        if ($data) {
                            foreach ($attributes as $attribute) {
                                $attr = $attribute[$parentTableName][$leftKeyName] ?? false;

                                if ($attr && true === isset($data[$attr])) {
                                    if ($data[$attr]) {
                                        $data[$attr]->deleteLock = $class->deleteLock;
                                    }
                                    $attribute[$parentTableName]->offsetSet($moduleName, $data[$attr]);
                                } else {
                                    $attribute[$parentTableName]->offsetSet($moduleName, null);
                                }
                            }
                        } else {
                            foreach ($attributes as $attribute) {
                                $attribute[$parentTableName]->offsetSet($moduleName, null);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute[$parentTableName]->offsetSet($moduleName, null);
                        }
                    }
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                if ($class->newTableName) {
                    $moduleName = $class->newTableName;
                } else {
                    $moduleName = $class->tableName . '_models';
                }

                if ($class->leftKeyName) {
                    $leftKeyName = $class->leftKeyName;
                } else {
                    $leftKeyName = $class->primaryKeyName;
                }

                if ($class->rightKeyName) {
                    $rightKeyName = $class->rightKeyName;
                } else {
                    $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                }
                // ->key로 바꿈
                $remapKey       = $class->keyName;
                $class->keyName = $leftKeyName;

                $seqs = [];

                foreach ($attributes as $attribute) {
                    if (false === isset($attribute[$leftKeyName])) {
                        throw new \Limepie\Exception($class->tableName . ' table not found left key: ' . $leftKeyName);
                    }

                    if (null !== $attribute[$leftKeyName]) {
                        $seqs[] = $attribute[$leftKeyName];
                    }
                }

                $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);

                $seqs = \array_unique($seqs);

                $args = [$seqs];

                foreach ($class->and as $key1 => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key1);
                    $args[] = $value;
                }

                $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                if ($data) {
                    $group = [];

                    foreach ($data as $key => $row) {
                        $group[$row[$rightKeyName]][$key] = $row;
                    }

                    if ($group) {
                        foreach ($attributes as $attribute) {
                            $attr = $attribute[$leftKeyName] ?? '';

                            if ($attr && true === isset($group[$attr])) {
                                if ($class->keyName === $remapKey) {
                                    $attribute->offsetSet($moduleName, new $class($this->getConnect(), $group[$attr]));
                                } else {
                                    $new = [];

                                    foreach ($group[$attr] as $key => $value) {
                                        if (false === \in_array($remapKey, $value->allColumns, true)) {
                                            throw new \Limepie\Exception($remapKey . ' column not found #4');
                                        }

                                        if (false === \array_key_exists($remapKey, $value->attributes)) {
                                            throw new \Limepie\Exception($remapKey . ' column is null, not match');
                                        }

                                        if ($class->secondKeyName) {
                                            $new[$value[$remapKey]][$value[$class->secondKeyName]] = $value;
                                        } else {
                                            $new[$value[$remapKey]] = $value;
                                        }
                                    }

                                    $attribute->offsetSet($moduleName, $tmp = new $class($this->getConnect(), $new));
                                    $tmp->deleteLock = $class->deleteLock;
                                }
                            } else {
                                $attribute->offsetSet($moduleName, null);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, null);
                        }
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        $attribute->offsetSet($moduleName, null);
                    }
                }
            }
        }

        if ($this->oneToManys ?? []) {
            foreach ($this->oneToManys as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    if ($class->newTableName) {
                        $moduleName = $class->newTableName;
                    } else {
                        $moduleName = $class->tableName . '_models';
                    }

                    if ($class->leftKeyName) {
                        $leftKeyName = $class->leftKeyName;
                    } else {
                        $leftKeyName = $class->primaryKeyName;
                    }

                    if ($class->rightKeyName) {
                        $rightKeyName = $class->rightKeyName;
                    } else {
                        $rightKeyName = $class->tableName . '_' . $class->primaryKeyName;
                    }
                    // ->key로 바꿈
                    $remapKey       = $class->keyName;
                    $class->keyName = $leftKeyName;

                    $seqs = [];

                    foreach ($attributes as $attribute) {
                        if (false === isset($attribute[$parentTableName][$leftKeyName])) {
                            throw new \Limepie\Exception($class->tableName . ' table not found left key: ' . $leftKeyName);
                        }

                        if (null !== $attribute[$parentTableName][$leftKeyName]) {
                            $seqs[] = $attribute[$parentTableName][$leftKeyName];
                        }
                    }
                    $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);
                    $seqs         = \array_unique($seqs);

                    $args = [$seqs];

                    foreach ($class->and as $key1 => $value) {
                        $functionName .= 'And' . \Limepie\camelize($key1);
                        $args[] = $value;
                    }

                    $data = \call_user_func_array([$class($this->getConnect()), $functionName], $args);

                    if ($data) {
                        $group = [];

                        foreach ($data as $key => $row) {
                            $group[$row[$rightKeyName]][$key] = $row;
                        }

                        if ($group) {
                            foreach ($attributes as $attribute) {
                                $attr = $attribute[$parentTableName][$leftKeyName] ?? '';

                                if ($attr && true === isset($group[$attr])) {
                                    if ($class->keyName === $remapKey) {
                                        $attribute[$parentTableName]->offsetSet($moduleName, $tmp = new $class($this->getConnect(), $group[$attr]));
                                        $tmp->deleteLock = $class->deleteLock;
                                    } else {
                                        $new = [];

                                        foreach ($group[$attr] as $key => $value) {
                                            if (false === \in_array($remapKey, $value->allColumns, true)) {
                                                throw new \Limepie\Exception($remapKey . ' column not found #3');
                                            }

                                            if (false === \array_key_exists($remapKey, $value->attributes)) {
                                                throw new \Limepie\Exception($remapKey . ' column is null, not match');
                                            }

                                            if ($class->secondKeyName) {
                                                $new[$value[$remapKey]][$value[$class->secondKeyName]] = $value;
                                            } else {
                                                $new[$value[$remapKey]] = $value;
                                            }
                                        }
                                        $attribute[$parentTableName]->offsetSet($moduleName, $tmp = new $class($this->getConnect(), $new));
                                        $tmp->deleteLock = $class->deleteLock;
                                    }
                                } else {
                                    $attribute[$parentTableName]->offsetSet($moduleName, null);
                                }
                            }
                        } else {
                            foreach ($attributes as $attribute) {
                                $attribute[$parentTableName]->offsetSet($moduleName, null);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute[$parentTableName]->offsetSet($moduleName, null);
                        }
                    }
                }
            }
        }

        return $attributes;
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
            throw new \Limepie\Exception($this->tableName . ' db connection not found');
        }

        return $this->pdo;
    }

    public function setConnect(\Pdo $connect)
    {
        return $this->pdo = $connect;
    }

    public function filter(\Closure $callback = null)
    {
        if (true === isset($callback) && $callback) {
            return $callback($this);
        }
    }

    public function key(?string $keyName = null)
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

    public function replace()
    {
    }

    public function create()
    {
        $columns = [];
        $binds   = [];
        $values  = [];

        foreach ($this->allColumns as $column) {
            if ($this->sequenceName === $column) {
            } else {
                if ('created_ts' === $column || 'updated_ts' === $column) {
                } elseif ('ip' === $column) {
                    $columns[]            = '`' . $column . '`';
                    $binds[':' . $column] = $this->attributes[$column] ?? \Limepie\getIp();
                    $values[]             = 'inet6_aton(:' . $column . ')';
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && true === \is_array($this->attributes[$column])
                    && false === isset($this->rawAttributes[$column])
                ) {
                    $columns[] = '`' . $column . '`';
                    $value     = $this->attributes[$column];

                    if (true === \is_null($value)) {
                        throw new \Limepie\Exception('empty point value');
                    }
                    $binds[':' . $column . '1'] = $value[0];
                    $binds[':' . $column . '2'] = $value[1];

                    $values[] = 'point(:' . $column . '1, :' . $column . '2)';
                } elseif (true === \array_key_exists($column, $this->attributes)) {
                    $value = $this->attributes[$column];

                    if (true === isset($this->dataStyles[$column])) {
                        switch ($this->dataStyles[$column]) {
                            case 'serialize':
                                $value = \serialize($value);

                                break;
                            case 'base64':
                                $value = \base64_encode(\serialize($value));

                                break;
                            case 'gz':
                                $value = \gzcompress(\serialize($value), 9);

                                break;
                            case 'json':
                                $value = \json_encode($value);

                                break;
                            case 'yml':
                            case 'yaml':
                                $value = \yaml_emit($value);

                                break;
                        }
                    }

                    if (true === isset($this->rawAttributes[$column])) {
                        $columns[] = "`{$this->tableName}`." . '`' . $column . '`';
                        $values[]  = \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $binds += $value;
                        } else {
                            throw new \Limepie\Exception($column . ' raw bind error');
                        }
                        $values[] = \str_replace('?', ':' . $column, $this->rawAttributes[$column]);
                    } else {
                        $columns[]            = '`' . $column . '`';
                        $binds[':' . $column] = $value;
                        $values[]             = ':' . $column;
                    }
                }
            }
        }
        $column = \implode(', ', $columns);
        $values = \implode(', ', $values);
        $sql    = <<<SQL
            INSERT INTO
                `{$this->tableName}`
            ({$column})
                VALUES
            ({$values})
        SQL;

        $primaryKey = '';

        if (static::$debug) {
            $this->print($sql, $binds);
            \Limepie\Timer::start();
        }

        if ($this->sequenceName) {
            $primaryKey                              = $this->getConnect()->setAndGetSequnce($sql, $binds);
            $this->attributes[$this->primaryKeyName] = $primaryKey;
        } else {
            if ($this->getConnect()->set($sql, $binds)) {
                $primaryKey = $this->attributes[$this->primaryKeyName];
            }
        }

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
        }

        if ($primaryKey) {
            $this->primaryKeyValue = $primaryKey;

            return $this;
        }

        return false;
    }

    // TODO: db에서 가져온것과 비교해서 바뀌지 않으면 업데이트 하지 말기
    public function update($checkUpdatedTs = false)
    {
        $columns = [];

        $binds = [
            ':' . $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
        ];

        foreach ($this->allColumns as $column) {
            if ($this->sequenceName === $column) {
            } else {
                if ('created_ts' === $column || 'updated_ts' === $column) {
                } elseif ('ip' === $column) {
                    $columns[]            = "`{$this->tableName}`." . '`' . $column . '` = inet6_aton(:' . $column . ')';
                    $binds[':' . $column] = $this->attributes[$column] ?? \Limepie\getIp();
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && true === \is_array($this->attributes[$column])
                    && false === isset($this->rawAttributes[$column])
                ) {
                    $value = $this->attributes[$column];

                    if (true === \is_null($value)) {
                        throw new \Limepie\Exception('empty point value');
                    }

                    $columns[] = "`{$this->tableName}`." . '`' . $column . '` = point(:' . $column . '1, :' . $column . '2)';

                    $binds[':' . $column . '1'] = $value[0];
                    $binds[':' . $column . '2'] = $value[1];
                } elseif (true === \array_key_exists($column, $this->attributes)) {
                    $value = $this->attributes[$column];

                    if (true === isset($this->dataStyles[$column])) {
                        switch ($this->dataStyles[$column]) {
                            case 'serialize':
                                $value = \serialize($value);

                                break;
                            case 'base64':
                                $value = \base64_encode(\serialize($value));

                                break;
                            case 'gz':
                                $value = \gzcompress(\serialize($value), 9);

                                break;
                            case 'json':
                                $value = \json_encode($value);

                                break;
                            case 'yml':
                            case 'yaml':
                                $value = \yaml_emit($value);

                                break;
                        }
                    }

                    if (true === isset($this->rawAttributes[$column])) {
                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $binds += $value;
                        } else {
                            throw new \Limepie\Exception($column . ' raw bind error');
                        }
                    } else {
                        $columns[]            = "`{$this->tableName}`." . '`' . $column . '` = :' . $column;
                        $binds[':' . $column] = $value;
                    }
                }
            }
        }
        $column = \implode(', ', $columns);
        $where  = $this->primaryKeyName;
        $sql    = <<<SQL
            UPDATE
                `{$this->tableName}`
            SET
                {$column}
            WHERE
            `{$where}` = :{$where}
        SQL;

        if (true === $checkUpdatedTs) {
            $sql .= ' AND updated_ts = :check_updated_ts';
            $binds[':check_updated_ts'] = $this->attributes['updated_ts'];
        }

        if (static::$debug) {
            $this->print($sql, $binds);
            \Limepie\Timer::start();
        }

        if ($this->getConnect()->set($sql, $binds)) {
            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
            }

            return $this;
        }

        return false;
    }

    public function delete(bool $recursive = false)
    {
        if ($recursive) {
            return $this->objectToDelete();
        }

        return $this->doDelete();
    }

    private function iteratorToDelete(array | self $attributes)
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
        if (true === isset($this->attributes[$this->primaryKeyName])) {
            $this->iteratorToDelete($this->attributes);
            $this->doDelete();

            return true;
        }

        foreach ($this->attributes as $index => $attribute) {
            if (true === isset($attribute[$attribute->primaryKeyName])) {
                $this->iteratorToDelete($attribute);
                $attribute($this->getConnect())->doDelete();
            }
        }

        return true;
    }

    public function doDelete() : self | bool
    {
        if (true === isset($this->attributes[$this->primaryKeyName])) {
            $sql = <<<SQL
                DELETE
                FROM
                    `{$this->tableName}`
                WHERE
                    `{$this->primaryKeyName}` = :{$this->primaryKeyName}
            SQL;

            $binds = [
                $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
            ];

            if (static::$debug) {
                $this->print($sql, $binds);
                \Limepie\Timer::start();
            }

            if ($this->getConnect()->set($sql, $binds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
                }
                $this->primaryKeyValue = '';
                $this->attributes      = [];

                return $this;
            }

            return false;
        }
        $result = false;

        foreach ($this->attributes as $index => &$object) {
            $sql = <<<SQL
                DELETE
                FROM
                    `{$object->tableName}`
                WHERE
                    `{$object->primaryKeyName}` = :{$object->primaryKeyName}
            SQL;

            $binds = [
                $object->primaryKeyName => $object->attributes[$object->primaryKeyName],
            ];

            if (static::$debug) {
                $this->print($sql, $binds);
                \Limepie\Timer::start();
            }

            if ($this->getConnect()->set($sql, $binds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
                }
                $object->primaryKeyValue = '';
                $object->attributes      = [];
                unset($ojbect);
                $result = true;
            }
        }

        if ($result) {
            return $this;
        }

        return false;
    }

    private function getSelectColumns(string $prefixString = '') : string
    {
        $prefix = '';

        if ($prefixString) {
            $prefix = $prefixString . '_';
        }
        $columns = [];

        if (false === \in_array($this->primaryKeyName, $this->selectColumns, true)) {
            \array_unshift($this->selectColumns, $this->primaryKeyName);
        }

        foreach ($this->selectColumns as $column => $alias) {
            if (false === \in_array($alias, $this->removeColumns, true)) {
                if (true === \is_numeric($column)) {
                    if ('ip' === $alias) {
                        $columns[] = "inet6_ntoa(`{$this->tableAliasName}`." . '`' . $alias . '`) AS `' . $prefix . $alias . '`';
                    } elseif (
                        true === isset($this->dataStyles[$alias])
                        && 'point' == $this->dataStyles[$alias]
                    ) {
                        $columns[] = "ST_AsText(`{$this->tableAliasName}`." . '`' . $alias . '`) AS `' . $prefix . $alias . '`';
                    } else {
                        $columns[] = "`{$this->tableAliasName}`." . '`' . $alias . '`' . ($prefix ? ' AS `' . $prefix . $alias . '`' : '');
                    }
                } else {
                    if (true === \is_object($alias)) {
                        (object) $alias;
                        $aliasString = $alias->aliasName ? ' AS `' . ($prefix ? $prefix : '') . $alias->aliasName . '`' : '';

                        if ($alias->format) {
                            $addColumns = \explode('_with_', $alias->columnName);
                            $values     = [];

                            foreach ($addColumns as $addColumn) {
                                $values[] = "`{$this->tableAliasName}`." . '`' . $addColumn . '`';
                            }

                            try {
                                $columns[] = \vsprintf($alias->format, $values) . $aliasString;
                            } catch (\Throwable $e) {
                                throw new \Limepie\Exception($e);
                            }
                        } else {
                            $columns[] = "`{$this->tableAliasName}`." . '`' . $alias->columnName . '`' . $aliasString;
                        }
                    } elseif (true === \is_array($alias)) {
                        $aliasString = (true === isset($alias[0]) && $alias[0] ? ' AS `' . ($prefix ? $prefix : '') . $alias[0] . '`' : '');
                        /*
                        $targetCategorySeqModels = (new ServiceModuleCategoryItemModel)($slave1)
                            ->addColumnServiceModuleSeqWithCurrentSeqAliasChildSeqs('super.GetFamilyTree(%s, %s)')
                            ->getByServiceModuleSeqAndUniqidAndIsClose($properties['service_module_seq'], $categoryUniqid, 0)
                            ;
                        */
                        if (true === isset($alias[1])) {
                            $addColumns = \explode('_with_', $column);
                            $values     = [];

                            foreach ($addColumns as $addColumn) {
                                $values[] = "`{$this->tableAliasName}`." . '`' . $addColumn . '`';
                            }

                            try {
                                $columns[] = \vsprintf($alias[1], $values) . $aliasString;
                            } catch (\Throwable $e) {
                                throw new \Limepie\Exception($e);
                            }
                        } else {
                            $columns[] = "`{$this->tableAliasName}`." . '`' . $column . '`' . $aliasString;
                        }
                    } else {
                        $aliasString = (true === isset($alias) && $alias ? ' AS `' . ($prefix ? $prefix : '') . $alias . '`' : '');
                        $columns[]   = "`{$this->tableAliasName}`." . '`' . $column . '`' . $aliasString;
                    }
                }
            }
        }

        return \implode(PHP_EOL . '        ' . ', ', $columns);
    }

    public function getOrderBy(?string $orderBy = null)
    {
        $sql = '';

        if ($orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $orderBy;
        } elseif ($this->orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $this->orderBy;
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

    public function removeAllColumns() : self
    {
        $this->selectColumns   = $this->fkColumns;
        $this->selectColumns[] = $this->primaryKeyName;

        return $this;
    }

    public function getAllColumns() : self
    {
        $this->selectColumns = $this->allColumns;

        return $this;
    }

    public function keyName(string $keyName, ?string $secondKeyName = null) : self
    {
        $this->keyName = $keyName;

        $this->secondKeyName = $secondKeyName;

        return $this;
    }

    private function buildJoin(string $name, array $arguments) : self
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
                ->relation(
                    (new SangpumDeal)->matchEwSangpumDealSeqWithSeq()
                )
                ->getsByIsSale(1)
            ;


            $joinModels = (new SangpumDeal($slave1))
                ->joinSeqWithSangpumDealSeq(
                    (new SangpumDealItem)
                        ->andIsClose(0)
                        ->addColumnSeqAliasAaaa()
                        ->addColumnIsCloseAliasBbbb('LENGTH(INSTR(%s, 1))')
                        ->relation(
                            (new SangpumDealItemExpose())
                                ->matchSeqWithSangpumDealItemSeq(),
                        )
                )
                ->leftJoinSangpumDealSeqWithSangpumDealSeq(new SangpumDealItemExtend)

                ->getsByIsSale(1)
            ;

            $joinModels = (new SangpumDeal($slave1))
                ->relation(
                    (new SangpumType)->matchSangpumTypeSeqWithSeq()->aliasType()
                )
                ->joinSangpumSeqWithSeq(
                    $sangpumModel = (new Sangpum())
                        ->andIsSale(1)
                        ->aliasSangpum()
                )
                ->relation(
                    (new SangpumType)->matchSangpumTypeSeqWithSeq()->aliasType2()
                    , $sangpumModel
                )
                ->getsByIsSaleAndLtSaleStartDtAndGtSaleEndDt(1, \date('Y-m-d H:i:s'), \date('Y-m-d H:i:s'))
            ;
        */
        if (1 === \preg_match('#(?P<type>left)?(j|J)oin(?P<leftKeyName>.*)With(?P<rightKeyName>.*)#', $name, $m)) {
            $this->joinModels[] = [
                'model' => $arguments[0],
                'left'  => \Limepie\decamelize($m['leftKeyName']),
                'right' => \Limepie\decamelize($m['rightKeyName']),
                'type'  => $m['type'] ? true : false,
            ];
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
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
        if (!$sql) {
            $sql = $this->query;
        }

        if (!$binds) {
            $binds = $this->binds;
        }
        \Limepie\Timer::start();
        $data  = $this->getConnect()->gets('EXPLAIN ' . $sql, $binds);
        $timer = \Limepie\Timer::stop();
        echo '<br /><br /><table class="model-debug">';

        foreach (\debug_backtrace() as $trace) {
            if (true === isset($trace['file'])) {
                if (false === \strpos($trace['file'], 'yejune/limepie/src/Limepie')) {
                    $filename = $trace['file'];
                    $line     = $trace['line'];

                    echo '<tr><th>file ' . $filename . ' on line ' . $line . ($timer ? ', explain timer (' . $timer . ')' : '') . '</th></tr>';

                    break;
                }
            }
        }
        echo '<tr><td>';
        echo (new \Doctrine\SqlFormatter\SqlFormatter)->format($this->replaceQueryBinds($sql, $binds)); //, $binds);

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
        //exit;
    }

    private function buildGetCount(string $name, array $arguments, int $offset) : int
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $condition .= $this->condition;
        $binds += $this->binds;

        $selectColumns = $this->getSelectColumns();
        $orderBy       = $this->getOrderBy();
        $limit         = $this->getLimit();
        $join          = '';

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin($this);
            $join           = $joinInfomation['join'];
            $selectColumns .= $joinInfomation['selectColumns'];
            $binds += $joinInfomation['binds'];

            if ($joinInfomation['condition']) {
                if ($condition) {
                    $condition .= ' ' . $joinInfomation['condition'];
                } else {
                    $condition = $joinInfomation['condition'];
                }
            }

            $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $sql = <<<SQL
            SELECT
                COUNT(*)
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$join}
            {$condition}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if (static::$debug) {
            $this->print(null, null);
            \Limepie\Timer::start();
        }

        if ($this->getConnect() instanceof \Pdo) {
            $data = $this->getConnect()->get1($sql, $binds, false);

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
            }

            return $data;
        }

        throw new \Limepie\Exception('lost connection');
    }

    private function buildGetSum(string $name, array $arguments, int $offset) : int | float
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $condition .= $this->condition;
        $binds += $this->binds;

        $selectColumns = $this->getSelectColumns();
        $orderBy       = $this->getOrderBy();
        $limit         = $this->getLimit();
        $join          = '';

        $sumColumn = $this->sumColumn;

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin($this);

            if ($joinInfomation['sumColumn']) {
                $sumColumn = $joinInfomation['sumColumn'];
            }
            $join = $joinInfomation['join'];
            $selectColumns .= $joinInfomation['selectColumns'];
            $binds += $joinInfomation['binds'];

            if ($joinInfomation['condition']) {
                if ($condition) {
                    $condition .= ' ' . $joinInfomation['condition'];
                } else {
                    $condition = $joinInfomation['condition'];
                }
            }

            $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $sql = <<<SQL
            SELECT
                COALESCE(SUM({$sumColumn}), 0)
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$join}
            {$condition}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if (static::$debug) {
            $this->print(null, null);
            \Limepie\Timer::start();
        }

        if ($this->getConnect() instanceof \Pdo) {
            $data = $this->getConnect()->get1($sql, $binds, false);

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
            }

            return \Limepie\decimal($data);
        }

        throw new \Limepie\Exception('lost connection');
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

    public function getJoin(Model $prevModel) : array
    {
        $andConditions = [];
        $join          = '';
        $selectColumns = '';
        $index         = -1;
        $condition     = '';
        $binds         = [];

        foreach ($this->joinModels as $joinModel) {
            $class          = $joinModel['model'];
            $tableName      = $class->tableName;
            $tableAliasName = $class->tableAliasName;
            $joinLeft       = $joinModel['left'];
            $joinRight      = $joinModel['right'];

            $join .= PHP_EOL . '    ';

            if ($joinModel['type']) {
                $join .= 'LEFT';
            } else {
                $join .= 'INNER';
            }

            $join .= ' JOIN'
                   . PHP_EOL . '        ' . ' `' . $tableName . '` AS `' . $tableAliasName . '`'
                   . PHP_EOL . '    ON'
                   . PHP_EOL . '        `' . $prevModel->tableAliasName . '`.`' . $joinLeft . '` = `' . $tableAliasName . '`.`' . $joinRight . '`';
            $join .= ' ' . \implode(', ', $this->forceIndexes);

            $selectColumns .= PHP_EOL . '        ' . ', ' . $class->getSelectColumns($tableAliasName);

            if ($class->binds) {
                $binds += $class->binds;
            }

            if ($class->condition) {
                $andConditions[] = $class->condition;
            }
            $prevModel = $class;
        }

        $condition = \implode(PHP_EOL . ' AND ', $andConditions);

        return [
            'binds'         => $binds,
            'condition'     => $condition,
            'join'          => $join,
            'selectColumns' => $selectColumns,
            'sumColumn'     => $class->sumColumn,
        ];
    }

    public function gets(array | string | null $sql = null, array $binds = []) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';
        $keyName               = $this->keyName;

        if (false === \is_string($sql)) {
            $args      = $sql;
            $orderBy   = $this->getOrderBy($args['order'] ?? null);
            $limit     = $this->getLimit();
            $condition = '';
            $binds     = [];
            $join      = '';

            if (true === isset($args['condition'])) {
                $condition = ' ' . $args['condition'];
            } else {
                if ($this->condition) {
                    $condition = '  ' . $this->condition;
                    $binds     = $this->binds;
                }
            }

            if (true === isset($args['binds'])) {
                $binds = $args['binds'];
            }

            if (!$condition && $this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }

            $selectColumns = $this->getSelectColumns();

            if ($this->joinModels) {
                $joinInfomation = $this->getJoin($this);
                $join           = $joinInfomation['join'];
                $selectColumns .= $joinInfomation['selectColumns'];
                $binds += $joinInfomation['binds'];

                if ($joinInfomation['condition']) {
                    if ($condition) {
                        $condition .= ' ' . $joinInfomation['condition'];
                    } else {
                        $condition = $joinInfomation['condition'];
                    }
                }

                $keyName = '';
            }

            if ($condition) {
                $condition = ' WHERE ' . $condition;
            }
            $forceIndex = \implode(', ', $this->forceIndexes);

            $sql = <<<SQL
                SELECT
                    {$selectColumns}
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$forceIndex}
                {$join}
                {$condition}
                {$orderBy}
                {$limit}
            SQL;
            $this->condition = $condition;
        }

        $this->query = $sql;
        $this->binds = $binds;

        if (static::$debug) {
            $this->print(null, null);
            \Limepie\Timer::start();
        }

        $data = $this->getConnect()->gets($sql, $binds, false);

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
        }

        $class = \get_called_class();

        $attributes = [];

        foreach ($data as $index => &$row) {
            foreach ($this->joinModels as $joinModelInfomation) {
                $joinModel          = $joinModelInfomation['model'];
                $joinClassAliasName = $joinModel->tableAliasName;
                $joinClassName      = $joinModel->tableName;

                $tmp = [];

                foreach ($row as $innerFieldName => &$innerFieldValue) {
                    if (0 === \strpos($innerFieldName, $joinClassAliasName . '_')) {
                        $tmp[\str_replace($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($row[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManys[$parentTableName] = $joinModel->oneToMany;
                }
            }

            if ($keyName) {
                if (false === \array_key_exists($this->keyName, $row)) {
                    throw new \Limepie\Exception('gets ' . $this->tableName . ' "' . $this->keyName . '" column not found #5');
                }

                $attributes[$row[$keyName]] = new $class($this->getConnect(), $row);
            } else {
                $attributes[] = new $class($this->getConnect(), $row);
            }
        }

        if ($attributes) {
            $attributes       = $this->getRelations($attributes);
            $this->attributes = $attributes;
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
        return new class($bind, $extraCondition, $extraBinds) {
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

    public function get(string | array | null $sql = null, array $binds = []) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';

        if (true === \is_array($sql) || null === $sql) {
            $args          = $sql;
            $selectColumns = $this->getSelectColumns();
            $condition     = '';
            $join          = '';
            $binds         = [];
            $orderBy       = $this->getOrderBy();

            if (true === isset($args['condition'])) {
                $condition = ' ' . $args['condition'];
            } else {
                if ($this->condition) {
                    $condition = '  ' . $this->condition;
                    $binds     = $this->binds;
                }
            }

            if (true === isset($args['binds'])) {
                $binds = $args['binds'];
            }

            if (!$condition && $this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }

            $selectColumns = $this->getSelectColumns();

            if ($this->joinModels) {
                $joinInfomation = $this->getJoin($this);
                $join           = $joinInfomation['join'];
                $selectColumns .= $joinInfomation['selectColumns'];
                $binds += $joinInfomation['binds'];

                if ($joinInfomation['condition']) {
                    if ($condition) {
                        $condition .= ' ' . $joinInfomation['condition'];
                    } else {
                        $condition = $joinInfomation['condition'];
                    }
                }

                $keyName = '';
            }

            if ($condition) {
                $condition = ' WHERE ' . $condition;
            }

            $sql = <<<SQL
                SELECT
                    {$selectColumns}
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$join}
                {$condition}
                {$orderBy}
                LIMIT 1
            SQL;

            $this->condition = $condition;
        }

        $this->query = $sql;
        $this->binds = $binds;

        if (static::$debug) {
            $this->print(null, null);
            \Limepie\Timer::start();
        }

        $attributes = $this->getConnect()->get($sql, $binds, false);

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
        }

        if ($attributes) {
            foreach ($this->joinModels as $joinModelInfomation) {
                $joinModel          = $joinModelInfomation['model'];
                $joinClassAliasName = $joinModel->tableAliasName;
                $joinClassName      = $joinModel->tableName;

                $tmp = [];

                foreach ($attributes as $innerFieldName => &$innerFieldValue) {
                    if (0 === \strpos($innerFieldName, $joinClassAliasName . '_')) {
                        $tmp[\str_replace($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($attributes[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManys[$parentTableName] = $joinModel->oneToMany;
                }
            }

            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    public function forceIndex(string $indexKey) : self
    {
        $this->forceIndexes[] = ' FORCE INDEX (`' . $indexKey . '`)';

        return $this;
    }

    private function buildSet(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new \Limepie\Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        $this->attributes[$columnName] = $arguments[0];

        return $this;
    }

    // $model->setRawLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
    private function buildSetRaw(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 6));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new \Limepie\Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        $this->rawAttributes[$columnName] = $arguments[0];
        $this->attributes[$columnName]    = $arguments[1] ?? null;

        return $this;
    }

    private function buildOrderBy(string $name, array $arguments) : self
    {
        if (1 === \preg_match('#orderBy(?P<column>.*)(?P<how>Asc|Desc)$#U', $name, $m)) {
            if (true === isset($arguments[0])) {
                $this->orderBy = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . \strtoupper($m['how']);
            } else {
                $this->orderBy = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ' . \strtoupper($m['how']);
            }
        } elseif (1 === \preg_match('#orderBy(?P<column>.*)#', $name, $m)) {
            if (true === isset($arguments[0])) {
                $this->orderBy = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . 'ASC';
            } else {
                $this->orderBy = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ASC';
            }
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildWhere(string $name, array $arguments, int $offset = 5)
    {
        [$this->condition, $this->binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        return $this;
    }

    private function buildSum(string $name, array $arguments, int $offset = 3) : self
    {
        $this->sumColumn = '`' . $this->tableAliasName . '`.`' . \Limepie\decamelize(\substr($name, $offset)) . '`';

        return $this;
    }

    public function and(string $key, $value = null) : self
    {
        return $this->buildAnd($key, [$value], 0);
    }

    private function buildAnd(string $name, array $arguments, int $offset = 3) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true) && false === isset($arguments[0])) {
            $this->condition .= ' AND ' . $operator;
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);

            if ($conds) {
                $this->condition .= ' AND ' . PHP_EOL . '        ' . \trim(\implode(PHP_EOL . '        ', $conds));
            }

            if ($binds) {
                $this->binds += $binds;
            }
        }

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
    private function buildCondition(string $name, array $arguments = [], int $offset = 9) : self
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

    private function buildOr(string $name, array $arguments = [], int $offset = 2) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true) && false === isset($arguments[0])) {
            $this->condition .= ($this->condition ? ' OR ' : '') . $operator;
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);
            $condition       = \trim(\implode(PHP_EOL . '        ', $conds));

            if ($condition) {
                $condition = ($this->condition ? ' OR ' : '') . PHP_EOL . '        ' . $condition;
                $this->condition .= $condition;
            }

            if ($binds) {
                $this->binds += $binds;
            }
        }

        return $this;
    }

    private function splitKey(string $name, int $offset = 0) : array
    {
        $whereKey = \trim(\Limepie\decamelize(\substr($name, $offset)), '_ ');

        if ($whereKey) {
            $matches   = \preg_split('#([^_]+])?(_and_|_or_)([^_]+])?#U', $whereKey, flags: \PREG_SPLIT_OFFSET_CAPTURE);
            $splitKeys = [];
            $prevMatch = [];
            $offset    = 0;

            foreach ($matches as $i => $match) {
                if ($prevMatch) {
                    $splitKeys[] = [
                        \str_repeat('(', \substr_count($prevMatch[0], '(_')), // open
                        \trim($prevMatch[0], '()_'), // key
                        \str_repeat(')', \substr_count($prevMatch[0], '_)')), // close
                        \strtoupper(\trim(\str_replace($prevMatch[0], '', \substr($whereKey, $offset, $match[1] - $offset)), '_')), // 기호
                    ];
                    $offset = $match[1];
                }
                $prevMatch = $match;
            }
            $splitKeys[] = [
                \str_repeat('(', \substr_count($prevMatch[0], '(_')), // open
                \trim($prevMatch[0], '()_'), // key
                \str_repeat(')', \substr_count($prevMatch[0], '_)')), // close
                \strtoupper(\trim(\str_replace($prevMatch[0], '', \substr($whereKey, $offset, $match[1] - $offset)), '_')), // 기호
            ];

            return $splitKeys;
        }

        return [];
    }

    // where, and, or등의 추가 구문을 붙이지 않고 처리
    private function getConditions(string $name, array $arguments, int $offset = 0) : array
    {
        $splitKeys = $this->splitKey($name, $offset);
        $binds     = [];
        $conds     = [];

        foreach ($splitKeys as $index => $splitKey) {
            ++$this->bindcount;

            [$open, $key, $close, $operator] = $splitKey;

            $bindKeyname = $this->tableAliasName . '_' . $key . '_' . $this->bindcount;

            if (true === \is_object($arguments)) {
                throw new \Limepie\Exception($key . ' argument error');
            }

            if (false === \array_key_exists($index, $arguments)) {
                // \pr($name, $arguments, $offset);
                throw new \Limepie\Exception($key . ': numbers of columns of arguments do not match');
            }

            if (0 === \strpos($key, 'between_')) {
                $fixedKey = \substr($key, 8);

                $queryString = "`{$this->tableAliasName}`." . '`' . $fixedKey . '`' . ' BETWEEN :' . $bindKeyname . '_a AND :' . $bindKeyname . '_b';

                $binds[':' . $bindKeyname . '_a'] = $arguments[$index][0];
                $binds[':' . $bindKeyname . '_b'] = $arguments[$index][1];
            } elseif ($arguments[$index] && true === \is_array($arguments[$index])) {
                $bindkeys = [];

                if (false === \in_array($key, $this->allColumns, true)) {
                    throw new \Limepie\Exception($this->tableName . ' table: ' . $key . ' field match error');
                }

                foreach ($arguments[$index] as $bindindex => $bindvalue) {
                    $bindkey         = ':' . $bindKeyname . '_' . $bindindex;
                    $bindkeys[]      = $bindkey;
                    $binds[$bindkey] = $bindvalue;
                }
                $queryString = "`{$this->tableAliasName}`.`{$key}` IN (" . \implode(', ', $bindkeys) . ')';
            } else {
                $fixedKey   = \substr($key, 3);
                $whereValue = $arguments[$index];

                if (true === \is_object($whereValue)) {
                    if (\property_exists($whereValue, 'extraCondition')) {
                        $leftCondition = \sprintf(
                            $whereValue->extraCondition,
                            "`{$this->tableAliasName}`." . '`' . $fixedKey . '`'
                        );

                        // null 인경우 mysql에서 is null, is not null 사용하므로 bind 안함
                        if (null === $whereValue->bind) {
                        } else {
                            $binds[':' . $bindKeyname] = $whereValue->bind;
                        }

                        if (true === \is_array($whereValue->extraBinds) && $whereValue->extraBinds) {
                            $binds += $whereValue->extraBinds;
                        }
                    } else {
                        throw new \Limepie\Exception($key . ' argument error');
                    }
                } else {
                    $leftCondition = "`{$this->tableAliasName}`." . '`' . $fixedKey . '`';

                    // null 인경우 mysql에서 is null, is not null 사용하므로 bind 안함
                    if (null === $whereValue) {
                    } else {
                        $binds[':' . $bindKeyname] = $whereValue;
                    }
                }

                if (0 === \strpos($key, 'gt_')) {
                    $queryString = $leftCondition . ' > :' . $bindKeyname;
                } elseif (0 === \strpos($key, 'lt_')) {
                    $queryString = $leftCondition . ' < :' . $bindKeyname;
                } elseif (0 === \strpos($key, 'ge_')) {
                    $queryString = $leftCondition . ' >= :' . $bindKeyname;
                } elseif (0 === \strpos($key, 'le_')) {
                    $queryString = $leftCondition . ' <= :' . $bindKeyname;
                } elseif (0 === \strpos($key, 'eq_')) {
                    $queryString = $leftCondition . ' = :' . $bindKeyname;
                } elseif (0 === \strpos($key, 'ne_')) {
                    if (null === $arguments[$index]) {
                        $queryString = $leftCondition . ' IS NOT NULL';
                    } else {
                        $queryString = $leftCondition . ' != :' . $bindKeyname;
                    }
                } elseif (0 === \strpos($key, 'lk_')) {
                    $queryString = $leftCondition . ' like concat("%", :' . $bindKeyname . ', "%")';
                } else {
                    if (null === $arguments[$index]) {
                        $queryString = "`{$this->tableAliasName}`." . '`' . $key . '` IS NULL';
                    } else {
                        if ('ip' == $key) {
                            $queryString = "`{$this->tableAliasName}`." . '`' . $key . '`' . ' = inet6_aton(:' . $bindKeyname . ')';
                        } else {
                            $queryString = "`{$this->tableAliasName}`." . '`' . $key . '`' . ' = :' . $bindKeyname;
                        }
                    }
                }
            }

            if ($queryString) {
                $conds[] = $open . $queryString . $close;
            }

            if ($operator) {
                $conds[] = $operator;
            }
        }

        return [$conds, $binds];
    }

    private function buildKeyName(string $name, array $arguments) : self
    {
        if (1 === \preg_match('#keyName(?P<leftKeyName>.*)(With(?P<rightKeyName>.*))?$#U', $name, $m)) {
            $this->keyName = \Limepie\decamelize($m['leftKeyName']);

            if (true === isset($m['rightKeyName'])) {
                $this->secondKeyName = \Limepie\decamelize($m['rightKeyName']);
            }
        } else {
            $this->keyName = \Limepie\decamelize(\substr($name, 7));
        }

        return $this;
    }

    private function buildAlias(string $name, array $arguments) : self
    {
        $this->newTableName = \Limepie\decamelize(\substr($name, 5));
        //$this->tableAliasName = \Limepie\decamelize(\substr($name, 5));

        return $this;
    }

    /**
     * @example
     *     $userModels = (new UserModel)($slave1)
     *         ->addColumn('seq', 'cash', '(SELECT COALESCE(SUM(amount), 0) FROM point WHERE to_user_seq = %s AND status = 1 AND expired_ts > now())')
     */
    public function addColumn(string $columnName, ?string $aliasName = null, ?string $format = null)
    {
        if (null === $aliasName) {
            $this->selectColumns[$columnName] = null;
        } else {
            $this->selectColumns[$aliasName] = new class($columnName, $aliasName, $format) {
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
            $columnName = \Limepie\decamelize(\substr($name, 8));
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

    private function buildMatch(string $name, $arguments) : self
    {
        if (1 === \preg_match('#match(All)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)$#U', $name, $m)) {
            $this->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $this->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildGetBy(string $name, array $arguments, int $offset) : self | null
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $condition .= $this->condition;
        $binds += $this->binds;

        $selectColumns = $this->getSelectColumns();
        $orderBy       = $this->getOrderBy();
        $limit         = $this->getLimit();
        $join          = '';

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin($this);
            $join           = $joinInfomation['join'];
            $selectColumns .= $joinInfomation['selectColumns'];
            $binds += $joinInfomation['binds'];

            if ($joinInfomation['condition']) {
                if ($condition) {
                    $condition .= ' ' . $joinInfomation['condition'];
                } else {
                    $condition = $joinInfomation['condition'];
                }
            }

            $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $sql = <<<SQL
            SELECT
                {$selectColumns}
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$join}
            {$condition}
            {$orderBy}
            {$limit}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if ($this->getConnect() instanceof \Pdo) {
            if (static::$debug) {
                $this->print(null, null);
                \Limepie\Timer::start();
            }

            $attributes = $this->getConnect()->get($sql, $binds, false);

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
            }
        } else {
            throw new \Limepie\Exception('lost connection');
        }

        if ($attributes) {
            $attributes = $this->buildDataType($attributes);

            foreach ($this->joinModels as $joinModelInfomation) {
                $joinModel          = $joinModelInfomation['model'];
                $joinClassAliasName = $joinModel->tableAliasName;
                $joinClassName      = $joinModel->tableName;

                $tmp = [];

                foreach ($attributes as $innerFieldName => &$innerFieldValue) {
                    if (0 === \strpos($innerFieldName, $joinClassAliasName . '_')) {
                        $tmp[\str_replace($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($attributes[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManys[$parentTableName] = $joinModel->oneToMany;
                }
            }

            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    private function buildGetsBy(string $name, array $arguments, int $offset) : self | null
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';

        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $condition .= $this->condition;
        $binds += $this->binds;

        $selectColumns = $this->getSelectColumns();
        $orderBy       = $this->getOrderBy();
        $limit         = $this->getLimit();
        $join          = '';
        $forceIndex    = \implode(', ', $this->forceIndexes);
        $keyName       = $this->keyName;

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin($this);
            $join           = $joinInfomation['join'];
            $selectColumns .= $joinInfomation['selectColumns'];
            $binds += $joinInfomation['binds'];

            if ($joinInfomation['condition']) {
                if ($condition) {
                    $condition .= ' ' . $joinInfomation['condition'];
                } else {
                    $condition = $joinInfomation['condition'];
                }
            }

            $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $sql = <<<SQL
            SELECT
                {$selectColumns}
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$forceIndex}
            {$join}
            {$condition}
            {$orderBy}
            {$limit}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if (static::$debug) {
            $this->print(null, null);
            \Limepie\Timer::start();
        }

        $data = $this->getConnect()->gets($sql, $binds, false);

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . \Limepie\Timer::stop() . '</div>';
        }

        $attributes = [];

        $class = \get_called_class();

        foreach ($data as $index => &$row) {
            foreach ($this->joinModels as $joinModelInfomation) {
                $joinModel          = $joinModelInfomation['model'];
                $joinClassAliasName = $joinModel->tableAliasName;
                $joinClassName      = $joinModel->tableName;

                $tmp = [];

                foreach ($row as $innerFieldName => &$innerFieldValue) {
                    if (0 === \strpos($innerFieldName, $joinClassAliasName . '_')) {
                        $tmp[\str_replace($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($row[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManys[$parentTableName] = $joinModel->oneToMany;
                }
            }

            if ($keyName) {
                if (false === \array_key_exists($this->keyName, $row)) {
                    throw new \Limepie\Exception('gets by ' . $this->tableName . ' "' . $this->keyName . '" column not found #7');
                }

                $attributes[$row[$keyName]] = new $class($this->getConnect(), $row);
            } else {
                $attributes[] = new $class($this->getConnect(), $row);
            }
        }
        unset($row);

        if ($attributes) {
            $this->attributes = $this->getRelations($attributes);

            return $this;
        }

        return $this->empty();
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

                if (true === isset($binds[':' . $matches[2]])) {
                    $value = $binds[':' . $matches[2]];
                } else {
                    $value = $binds[$matches[2]] ?? null;
                }

                if (true === \is_numeric($value)) {
                    return $value;
                }

                return static::escapeFunction($value);
            },
            $query
        );
    }

    /**
     * Escape binds of a SQL query.
     *
     * @param mixed $parameter
     */
    private static function escapeFunction($parameter) : string
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

    private function empty()
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
        string | int | null $line = null
    ) {
        static::$debug = true;

        if (!$filename) {
            $trace    = \debug_backtrace()[0];
            $filename = $trace['file'];
            $line     = $trace['line'];
        }
        echo 'debug on: file ' . $filename . ' on line ' . $line . '<br />';
    }
}
