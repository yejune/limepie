<?php declare(strict_types=1);

namespace Limepie;

class Model extends ArrayObject
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

    public $functions = [];

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

    public static $debug = false;

    public static function newInstance(\Pdo $pdo = null, $attributes = []) : self
    {
        return new self($pdo, $attributes);
    }

    public function __construct(\Pdo $pdo = null, $attributes = [])
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

    public function __call($name, $arguments)
    {
        // if ('gets' === $name) {
        //     return $this->buildGets($name, $arguments);
        // } elseif ('get' === $name) {
        //     return $this->buildGet($name, $arguments);
        // } else
        if (0 === \strpos($name, 'orderBy')) {
            return $this->buildOrderBy($name, $arguments);
        }

        if (0 === \strpos($name, 'condition')) {
            return $this->buildCondition($name, $arguments);
        }

        if (0 === \strpos($name, 'where')) {
            return $this->buildWhere($name, $arguments);
        }

        if (0 === \strpos($name, 'and')) {
            return $this->buildAnd($name, $arguments);
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
            return $this->buildGetBy($name, $arguments);
        }

        if (0 === \strpos($name, 'getCount')) {
            return $this->buildGetCount($name, $arguments);
        }

        if (0 === \strpos($name, 'getsAllBy')) {
            $this->getAllColumns();

            return $this->buildGetsBy($name, $arguments, 9);
        }

        if (0 === \strpos($name, 'getsBy')) {
            return $this->buildGetsBy($name, $arguments);
        }

        if (0 === \strpos($name, 'addColumn')) {
            return $this->buildAddColumn($name, $arguments);
        }

        if (0 === \strpos($name, 'set')) {
            return $this->buildSet($name, $arguments);
        }

        if (0 === \strpos($name, 'get')) { // get column
            return $this->buildGetColumn($name, $arguments);
        }

        if (0 === \strpos($name, 'gt')) {
            return $this->buildGt($name, $arguments);
        }

        if (0 === \strpos($name, 'lt')) {
            return $this->buildLt($name, $arguments);
        }

        if (0 === \strpos($name, 'ge')) {
            return $this->buildGe($name, $arguments);
        }

        if (0 === \strpos($name, 'le')) {
            return $this->buildLe($name, $arguments);
        }

        if (0 === \strpos($name, 'eq')) {
            return $this->buildEq($name, $arguments);
        }

        if (0 === \strpos($name, 'ne')) {
            return $this->buildNe($name, $arguments);
        }

        if (0 === \strpos($name, 'lk')) {
            return $this->buildLk($name, $arguments);
        }

        if (0 === \strpos($name, 'between')) {
            return $this->buildBetween($name, $arguments);
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

    public function setAttribute($column, $attribute)
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

    public function getRelation($attributes)
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                $parent = null;

                if (true === \is_array($class)) {
                    $parent = $class[1];
                    $class  = $class[0];
                } elseif ($class->parent) {
                    $parent = $class->parent;
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

                $functionName = 'getBy' . \Limepie\camelize($rightKeyName);

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    if (false === \array_key_exists($leftKeyName, $attributes[$parentTableName]->toArray())) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$parentTableName][$leftKeyName]];
                } else {
                    if (false === \array_key_exists($leftKeyName, $attributes)) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$leftKeyName]];
                }

                foreach ($class->and as $key => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key);
                    $args[] = $value;
                }

                $connect = $class->getConnectOrNull();

                if (!$connect) {
                    $connect = $this->getConnect();
                }

                $class->keyName = $rightKeyName;

                //\pr($class->condition, $functionName);
                //pr([$class($connect), $functionName], var_dump($args));
                $data = \call_user_func_array([$class($connect), $functionName], $args);

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    if ($class->newTableName) {
                        $attributes[$parentTableName][$class->newTableName] = $data;
                    } else {
                        $attributes[$parentTableName][$class->tableName . '_model'] = $data;
                    }
                } else {
                    if ($class->newTableName) {
                        $attributes[$class->newTableName] = $data;
                    } else {
                        $attributes[$class->tableName . '_model'] = $data;
                    }
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                $parent = null;

                if (true === \is_array($class)) {
                    $parent = $class[1];
                    $class  = $class[0];
                } elseif ($class->parent) {
                    $parent = $class->parent;
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

                $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    if (false === \array_key_exists($leftKeyName, $attributes[$parentTableName])) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$parentTableName][$leftKeyName]];
                } else {
                    if (false === \array_key_exists($leftKeyName, $attributes)) {
                        throw new \Limepie\Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
                    }

                    $args = [$attributes[$leftKeyName]];
                }

                foreach ($class->and as $key1 => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key1);
                    $args[] = $value;
                }

                $connect = $class->getConnectOrNull();

                if (!$connect) {
                    $connect = $this->getConnect();
                }

                $data = \call_user_func_array([$class($connect), $functionName], $args);

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    if ($class->newTableName) {
                        $attributes[$parentTableName][$class->newTableName] = $data;
                    } else {
                        $attributes[$parentTableName][$class->tableName . '_models'] = $data;
                    }
                } else {
                    if ($class->newTableName) {
                        $attributes[$class->newTableName] = $data;
                    } else {
                        $attributes[$class->tableName . '_models'] = $data;
                    }
                }
            }
        }

        return $attributes;
    }

    public function getRelations($attributes)
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                $parent = null;

                if (true === \is_array($class)) {
                    $parent = $class[1];
                    $class  = $class[0];
                } elseif ($class->parent) {
                    $parent = $class->parent;
                }

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

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    foreach ($attributes as $row) {
                        if (true === \array_key_exists($leftKeyName, $row[$parentTableName]->toArray())) {
                            //if (true === isset($row[$leftKeyName])) {
                            $seqs[] = $row[$parentTableName][$leftKeyName];
                        } else {
                            throw new \Limepie\Exception($this->tableName . ' table ' . $leftKeyName . ' column not found #1');
                        }
                    }
                } else {
                    foreach ($attributes as $row) {
                        if (true === \array_key_exists($leftKeyName, $row->toArray())) {
                            //if (true === isset($row[$leftKeyName])) {
                            $seqs[] = $row[$leftKeyName];
                        } else {
                            throw new \Limepie\Exception($this->tableName . ' table ' . $leftKeyName . ' column not found #2');
                        }
                    }
                }

                if ($seqs) {
                    $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);
                    $args         = [$seqs];

                    foreach ($class->and as $key => $value) {
                        $functionName .= 'And' . \Limepie\camelize($key);
                        $args[] = $value;
                    }
                    $connect = $class->getConnectOrNull();

                    if (!$connect) {
                        $connect = $this->getConnect();
                    }

                    $class->keyName = $rightKeyName;
                    $data           = \call_user_func_array([$class($connect), $functionName], $args);

                    if ($parent) {
                        $parentClass = true === \is_string($parent) ? new $parent : $parent;

                        if ($parentClass->newTableName) {
                            $parentTableName = $parentClass->newTableName;
                        } else {
                            $parentTableName = $parentClass->tableName . '_model';
                        }

                        if ($data) {
                            foreach ($attributes as $attribute) {
                                $attr = $attribute[$parentTableName][$leftKeyName] ?? false;

                                if ($attr && true === isset($data[$attr])) {
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
                        if ($data) {
                            foreach ($attributes as $attribute) {
                                $attr = $attribute[$leftKeyName] ?? false;

                                if ($attr && true === isset($data[$attr])) {
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
                    }
                } else {
                    if ($parent) {
                        $parentClass = true === \is_string($parent) ? new $parent : $parent;

                        if ($parentClass->newTableName) {
                            $parentTableName = $parentClass->newTableName;
                        } else {
                            $parentTableName = $parentClass->tableName . '_model';
                        }

                        foreach ($attributes as $attribute) {
                            $attribute[$parentTableName]->offsetSet($moduleName, null);
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, null);
                        }
                    }
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                $parent = null;

                if (true === \is_array($class)) {
                    $parent = $class[1];
                    $class  = $class[0];
                } elseif ($class->parent) {
                    $parent = $class->parent;
                }

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

                if ($parent) {
                    $parentClass = true === \is_string($parent) ? new $parent : $parent;

                    if ($parentClass->newTableName) {
                        $parentTableName = $parentClass->newTableName;
                    } else {
                        $parentTableName = $parentClass->tableName . '_model';
                    }

                    foreach ($attributes as $attribute) {
                        if (false === isset($attribute[$parentTableName][$leftKeyName])) {
                            throw new \Limepie\Exception($class->tableName . ' table not found left key: ' . $leftKeyName);
                        }
                        $seqs[] = $attribute[$parentTableName][$leftKeyName];
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        if (false === isset($attribute[$leftKeyName])) {
                            throw new \Limepie\Exception($class->tableName . ' table not found left key: ' . $leftKeyName);
                        }
                        $seqs[] = $attribute[$leftKeyName];
                    }
                }
                $functionName = 'getsBy' . \Limepie\camelize($rightKeyName);

                $args = [$seqs];

                foreach ($class->and as $key1 => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key1);
                    $args[] = $value;
                }

                $connect = $class->getConnectOrNull();

                if (!$connect) {
                    $connect = $this->getConnect();
                }

                $data = \call_user_func_array([$class($connect), $functionName], $args);

                if ($data) {
                    $group = [];

                    foreach ($data as $key => $row) {
                        $group[$row[$rightKeyName]][$key] = $row;
                    }

                    if ($group) {
                        if ($parent) {
                            $parentClass = true === \is_string($parent) ? new $parent : $parent;

                            if ($parentClass->newTableName) {
                                $parentTableName = $parentClass->newTableName;
                            } else {
                                $parentTableName = $parentClass->tableName . '_model';
                            }

                            foreach ($attributes as $attribute) {
                                $attr = $attribute[$parentTableName][$leftKeyName] ?? '';

                                if ($attr && true === isset($group[$attr])) {
                                    if ($class->keyName === $remapKey) {
                                        $attribute[$parentTableName]->offsetSet($moduleName, new $class($this->getConnect(), $group[$attr]));
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
                                        $attribute[$parentTableName]->offsetSet($moduleName, new $class($this->getConnect(), $new));
                                    }
                                } else {
                                    $attribute[$parentTableName]->offsetSet($moduleName, null);
                                }
                            }
                        } else {
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

                                        $attribute->offsetSet($moduleName, new $class($this->getConnect(), $new));
                                    }
                                } else {
                                    $attribute->offsetSet($moduleName, null);
                                }
                            }
                        }
                    } else {
                        if ($parent) {
                            $parentClass = true === \is_string($parent) ? new $parent : $parent;

                            if ($parentClass->newTableName) {
                                $parentTableName = $parentClass->newTableName;
                            } else {
                                $parentTableName = $parentClass->tableName . '_model';
                            }

                            foreach ($attributes as $attribute) {
                                $attribute[$parentTableName]->offsetSet($moduleName, null);
                            }
                        } else {
                            foreach ($attributes as $attribute) {
                                $attribute->offsetSet($moduleName, null);
                            }
                        }
                    }
                } else {
                    if ($parent) {
                        $parentClass = true === \is_string($parent) ? new $parent : $parent;

                        if ($parentClass->newTableName) {
                            $parentTableName = $parentClass->newTableName;
                        } else {
                            $parentTableName = $parentClass->tableName . '_model';
                        }

                        foreach ($attributes as $attribute) {
                            $attribute[$parentTableName]->offsetSet($moduleName, null);
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, null);
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    // getBy, getsBy, getCountBy 구문 뒤의 구문을 분석하여 조건문을 만든다.
    public function getConditionAndBinds($whereKey, $arguments, $offset = 0)
    {
        $condition = '';
        $binds     = [];
        $conds     = [];

        [$conds, $binds] = $this->getConditions($whereKey, $arguments, $offset);
        $condition       = \trim(\implode(PHP_EOL . '        ', $conds));

        if ($condition) {
            $condition = 'WHERE ' . PHP_EOL . '        ' . $condition;
        }

        return [$condition, $binds];
    }

    public function match($leftKeyName, $rightKeyName) : Model
    {
        $this->leftKeyName  = $leftKeyName;
        $this->rightKeyName = $rightKeyName;

        return $this;
    }

    public function and($key, $value = null)
    {
        $this->and[$key] = $value;

        return $this;
    }

    public function relation($class, $parent = null)
    {
        return $this->oneToOne($class, $parent);
    }

    public function relations($class, $parent = null)
    {
        return $this->oneToMany($class, $parent);
    }

    public function oneToOne($class, $parent = null)
    {
        if ($parent) {
            $this->oneToOne[] = [$class, $parent];
        } else {
            $this->oneToOne[] = $class;
        }

        return $this;
    }

    public function oneToMany($class, $parent = null)
    {
        if ($parent) {
            $this->oneToMany[] = [$class, $parent];
        } else {
            $this->oneToMany[] = $class;
        }

        return $this;
    }

    public function limit($offset, $limit)
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

    public function getConnectOrNull()
    {
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

    public function key($keyName = null)
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
                    $binds[':' . $column] = \Limepie\getIp();
                    $values[]             = 'inet6_aton(:' . $column . ')';
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && true === \is_array($this->attributes[$column])
                    && false === isset($this->functions[$column])
                ) {
                    $columns[] = '`' . $column . '`';
                    $value     = $this->attributes[$column];

                    if (true === \is_null($value)) {
                        throw new \Limepie\Exception('empty point value');
                    }
                    $binds[':' . $column . '1'] = $value[0];
                    $binds[':' . $column . '2'] = $value[1];
                    $values[]                   = 'point(:' . $column . '1, :' . $column . '2)';
                } elseif (true === \array_key_exists($column, $this->attributes)) {
                    //if (true === isset($this->attributes[$column])) {

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

                    if (true === isset($this->functions[$column])) {
                        $columns[] = '`' . $column . '`';
                        $binds += $value;
                        $values[] = \str_replace('?', ':' . $column, $this->functions[$column]);
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

        if ($this->sequenceName) {
            $primaryKey                              = $this->getConnect()->setAndGetSequnce($sql, $binds);
            $this->attributes[$this->primaryKeyName] = $primaryKey;
        } else {
            if ($this->getConnect()->set($sql, $binds)) {
                $primaryKey = $this->attributes[$this->primaryKeyName];
            }
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
                    $binds[':' . $column] = \Limepie\getIp();
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && true === \is_array($this->attributes[$column])
                    && false === isset($this->functions[$column])
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

                    if (true === isset($this->functions[$column])) {
                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . \str_replace('?', ':' . $column, $this->functions[$column]);
                        $binds += $value;
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

        if ($this->getConnect()->set($sql, $binds)) {
            return $this;
        }

        return false;
    }

    public function delete($recursive = false)
    {
        if ($recursive) {
            return $this->objectToDelete();
        }

        return $this->doDelete();
    }

    public function objectToDelete()
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

    public function doDelete($debug = false)
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

            if ($debug || static::$debug) {
                $this->debug($sql, $binds);
            }

            if ($this->getConnect()->set($sql, $binds)) {
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

            if ($debug || static::$debug) {
                $this->debug($sql, $binds);
            }

            if ($this->getConnect()->set($sql, $binds)) {
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

    private function getSelectColumns($prefixString = '')
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
                    } elseif (true === isset($this->dataStyles[$alias]) && 'point' == $this->dataStyles[$alias]) {
                        $columns[] = "ST_AsText(`{$this->tableAliasName}`." . '`' . $alias . '`) AS `' . $prefix . $alias . '`';
                    } else {
                        $columns[] = "`{$this->tableAliasName}`." . '`' . $alias . '`' . ($prefix ? ' AS `' . $prefix . $alias . '`' : '');
                    }
                } else {
                    if (true === \is_array($alias)) {
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
                        $columns[]   = $column . $aliasString;
                    }
                }
            }
        }

        return \implode(PHP_EOL . '        ' . ', ', $columns);
    }

    public function getOrderBy($orderBy = null)
    {
        $sql = '';

        if ($orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $orderBy;
        } elseif ($this->orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $this->orderBy;
        }

        return $sql;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function addColumn($column, $aliasName = null)
    {
        if (null === $aliasName) {
            $this->selectColumns[$column] = null;
        } else {
            $this->selectColumns[$column] = $aliasName;
        }

        return $this;
    }

    public function addColumns(array $columns = [])
    {
        $this->selectColumns = \array_merge($this->selectColumns, $columns);

        return $this;
    }

    public function removeColumn($column)
    {
        $this->removeColumns[] = $column;

        return $this;
    }

    public function removeColumns(array $columns = [])
    {
        $this->removeColumns = $columns;

        return $this;
    }

    public function removeAllColumns()
    {
        //$this->removeColumns = $this->normalColumns;
        $this->selectColumns   = $this->fkColumns;
        $this->selectColumns[] = $this->primaryKeyName;

        return $this;
    }

    public function getAllColumns()
    {
        $this->selectColumns = $this->allColumns;

        return $this;
    }

    public function keyName($keyName, $secondKeyName = null)
    {
        $this->keyName = $keyName;

        $this->secondKeyName = $secondKeyName;

        return $this;
    }

    private function buildJoin($name, $arguments)
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
                )
                ->leftJoinSangpumDealSeqWithSangpumDealSeq(new SangpumDealItemExtend)
                ->relation(
                    (new SangpumDealItemExpose())
                        ->matchSeqWithSangpumDealItemSeq(),
                    SangpumDealItem::class
                )
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

    public function alias($tableName)
    {
        $this->newTableName   = $tableName;
        $this->tableAliasName = $tableName;

        return $this;
    }

    public function debug($sql = '', $binds = [])
    {
        if (!$sql) {
            $sql = $this->query;
        }

        if (!$binds) {
            $binds = $this->binds;
        }

        echo '<br /><table class="model-debug"><tr><td>';

        echo (new \Doctrine\SqlFormatter\SqlFormatter)->format($this->replaceQueryBinds($sql, $binds)); //, $binds);

        $data = $this->getConnect()->gets('EXPLAIN ' . $sql, $binds);

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
        echo '</td></tr></table><br />';
        //exit;
    }

    public function buildGetCount($name, $arguments)
    {
        //\pr($name, $arguments);
        //$whereKey            = \Limepie\decamelize(\substr($name, 10));
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, 8);
        $sql                 = <<<SQL
            SELECT
                COUNT(*)
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$condition}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        return $this->getConnect()->get1($sql, $binds);
    }

    public function open()
    {
        $this->conditions[] = ['string' => '('];

        return $this;
    }

    public function close()
    {
        $this->conditions[] = ['string' => ')'];

        return $this;
    }

    public function getJoin($prevModel)
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
        ];
    }

    public function gets(array | string $sql = null, array $binds = [])
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
                $condition = 'WHERE ' . $args['condition'];
            } else {
                if ($this->condition) {
                    $condition = ' WHERE ' . $this->condition;
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

                if ($condition) {
                    $condition .= PHP_EOL . '    AND ' . PHP_EOL . '        ' . $joinInfomation['condition'];
                } else {
                    $condition .= ' WHERE ' . PHP_EOL . '    ' . $joinInfomation['condition'];
                }
                $keyName = '';
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

        //\pr($sql);
        $this->binds = $binds;

        $data = $this->getConnect()->gets($sql, $binds, false);
        //\pr($data);
        if (static::$debug) {
            $this->debug();
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

    public function get(string | array $sql = null, array $binds = [])
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
            // TODO: buildGets와 같이 정리 필요

            if (true === isset($args['condition'])) {
                $condition = 'WHERE ' . $args['condition'];
            } else {
                if ($this->condition) {
                    $condition = ' WHERE ' . $this->condition;
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

                if ($condition) {
                    $condition .= PHP_EOL . '    AND ' . PHP_EOL . '        ' . $joinInfomation['condition'];
                } else {
                    $condition .= ' WHERE ' . PHP_EOL . '    ' . $joinInfomation['condition'];
                }
                $keyName = '';
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

        $attributes = $this->getConnect()->get($sql, $binds, false);

        if (static::$debug) {
            $this->debug();
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
            }

            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    public function forceIndex($indexKey)
    {
        $this->forceIndexes[] = ' FORCE INDEX (`' . $indexKey . '`)';

        return $this;
    }

    private function buildSet($name, $arguments)
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new \Limepie\Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        if (true === isset($arguments[1])) {
            // $model->setLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
            $this->functions[$columnName]  = $arguments[0];
            $this->attributes[$columnName] = $arguments[1];
        } else {
            $this->attributes[$columnName] = $arguments[0];
        }

        return $this;
    }

    private function buildOrderBy($name, $arguments)
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

    private function buildWhere($name, $arguments)
    {
        //$whereKey = \Limepie\decamelize(\substr($name, 7));

        [$this->condition, $this->binds] = $this->getConditionAndBinds($name, $arguments, 5);

        return $this;
    }

    private function buildAnd($name, $arguments, $offset = 3)
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true) && false === isset($arguments[0])) {
            $this->condition .= ' AND ' . $operator;
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);
            $condition       = \trim(\implode(PHP_EOL . '        ', $conds));

            if ($condition) {
                $condition = ' AND ' . PHP_EOL . '        ' . $condition;
                $this->condition .= $condition;
            }

            if ($binds) {
                $this->binds += $binds;
            }
        }

        return $this;
    }

    private function buildCondition($name, $arguments, $offset = 9)
    {
        /*
            $joinModels->{'getsByServiceSeqAnd((IsSaleAndLtSaleStartDtAndGtSaleEndDt)Or(IsSale))'}(
            $joinModels->{'conditionServiceSeqAnd((IsSaleAndLtSaleStartDtAndGtSaleEndDt)Or(IsSale))'}(
                $serviceSeq,
                1,
                \date('Y-m-d H:i:s'),
                \date('Y-m-d H:i:s'),
                2
            )
            $joinModels
                ->conditionServiceSeq($serviceSeq)
                ->{'and('}()
                ->{'condition(IsSaleAndLtSaleStartDtAndGtSaleEndDt)'}(1, \date('Y-m-d H:i:s'), \date('Y-m-d H:i:s'))
                ->{'or(IsSale)'}(2)
                ->{'condition)'}()
            ->gets()
            ;
        */
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

    private function buildOr($name, $arguments, $offset = 2)
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

    private function splitKey($name, $offset)
    {
        $whereKey = \trim(\Limepie\decamelize(\substr($name, $offset)), '_ ');

        if ($whereKey) {
            $matches = \preg_split('#([^_]+])?(_and_|_or_)([^_]+])?#U', $whereKey, flags: \PREG_SPLIT_OFFSET_CAPTURE);

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
    private function getConditions($name, $arguments, $offset = 0)
    {
        $splitKeys = $this->splitKey($name, $offset);
        $binds     = [];
        $conds     = [];

        foreach ($splitKeys as $index => $splitKey) {
            ++$this->bindcount;

            [$open, $key, $close, $operator] = $splitKey;

            $bindKeyname = $this->tableAliasName . '_' . $key . '_' . $this->bindcount;

            if (0 === \strpos($key, 'between_')) {
                $fixedKey = \substr($key, 8);

                $queryString = "`{$this->tableAliasName}`." . '`' . $fixedKey . '`' . ' BETWEEN :' . $bindKeyname . '_a AND :' . $bindKeyname . '_b';

                $binds[':' . $bindKeyname . '_a'] = $arguments[$index][0];
                $binds[':' . $bindKeyname . '_b'] = $arguments[$index][1];
            } elseif ($arguments[$index] && true === \is_array($arguments[$index])) {
                $bindkeys = [];

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
                        $queryString = "`{$this->tableAliasName}`." . '`' . $key . '`' . ' = :' . $bindKeyname;
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

    private function buildGe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        ++$this->bindcount;

        $this->conditions[] = [
            'string' => $key . ' >= :' . $key . '_' . $this->bindcount,
            'bind'   => [
                $key . '_' . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildLe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        ++$this->bindcount;

        $this->conditions[] = [
            'string' => $key . ' <= :' . $key . '_' . $this->bindcount,
            'bind'   => [
                $key . '_' . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildNe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        ++$this->bindcount;

        if (null === $arguments[0]) {
            $this->conditions[] = [
                'string' => $key . ' IS NOT NULL',
                'bind'   => [
                ],
            ];
        } else {
            $this->conditions[] = [
                'string' => $key . ' != :' . $key . '_' . $this->bindcount,
                'bind'   => [
                    $key . '_' . $this->bindcount => $arguments[0],
                ],
            ];
        }

        return $this;
    }

    private function buildLk($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        ++$this->bindcount;

        $this->conditions[] = [
            'string' => $key . ' like concat("%", :' . $key . '_' . $this->bindcount, ', "%")',
            'bind'   => [
                $key . '_' . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildBetween($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 7));

        ++$this->bindcount;

        $a = $key . '_' . $this->bindcount . '_a';
        $b = $key . '_' . $this->bindcount . '_b';

        $this->conditions[] = [
            'string' => $key . ' BETWEEN :' . $a . ' AND :' . $b,
            'bind'   => [
                $a => $arguments[0][0],
                $b => $arguments[0][1],
            ],
        ];

        return $this;
    }

    private function buildKeyName($name, $arguments)
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

    private function buildAlias($name, $arguments)
    {
        $this->newTableName = \Limepie\decamelize(\substr($name, 5));
        //$this->tableAliasName = \Limepie\decamelize(\substr($name, 5));

        return $this;
    }

    public function buildAddColumn($name, $arguments = [])
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

        if (isset($arguments[0])) {
            $this->addColumn($columnName, [$aliasName, $arguments[0]]);
        } else {
            $this->addColumn($columnName, [$aliasName]);
        }

        return $this;
    }

    public function move($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    private function buildMatch($name, $arguments)
    {
        if (1 === \preg_match('#match(All)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)$#U', $name, $m)) {
            $this->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $this->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildGetBy($name, $arguments, $offset = 5)
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

            if ($condition) {
                $condition .= PHP_EOL . '    AND ' . PHP_EOL . '        ' . $joinInfomation['condition'];
            } else {
                $condition .= ' WHERE ' . PHP_EOL . '    ' . $joinInfomation['condition'];
            }
            $keyName = '';
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
            $attributes = $this->getConnect()->get($sql, $binds, false);
        } else {
            throw new \Limepie\Exception('lost connection');
        }

        if (static::$debug) {
            $this->debug();
        }

        if ($attributes) {
            $attributes            = $this->buildDataType($attributes);
            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    private function buildGetsBy($name, $arguments, $offset = 6)
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';
        //$whereKey              = \Limepie\decamelize(\substr($name, $offset));
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $condition .= $this->condition;
        $binds += $this->binds;

        $selectColumns = $this->getSelectColumns();
        $orderBy       = $this->getOrderBy();
        $limit         = $this->getLimit();
        $join          = '';
        $forceIndex    = \implode(', ', $this->forceIndexes);
        $keyName       = $this->keyName;

        $selectColumns = $this->getSelectColumns();

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin($this);
            $join           = $joinInfomation['join'];
            $selectColumns .= $joinInfomation['selectColumns'];
            $binds += $joinInfomation['binds'];

            if ($condition) {
                $condition .= PHP_EOL . '    AND ' . PHP_EOL . '        ' . $joinInfomation['condition'];
            } else {
                $condition .= ' WHERE ' . PHP_EOL . '    ' . $joinInfomation['condition'];
            }
            $keyName = '';
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

        $data = $this->getConnect()->gets($sql, $binds, false);

        if (static::$debug) {
            $this->debug();
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

    private function iteratorToDelete($attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if ($attribute instanceof self) {
                $attribute($this->getConnect())->objectToDelete();
            } else {
                if (true === \is_array($attribute)) {
                    if (0 < \count($attribute)) {
                        foreach ($attribute as $k2 => $v2) {
                            if ($v2 instanceof self) {
                                $v2($this->getConnect())->objectToDelete();
                            }
                        }
                    }
                }
            }
        }

        return true;
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
                    $value = $binds[$matches[2]];
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
}
