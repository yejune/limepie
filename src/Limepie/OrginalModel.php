<?php declare(strict_types=1);

namespace Limepie;

class OriginalModel implements \Iterator, \ArrayAccess, \Countable
{
    public $pdo;

    public $dataTypes = [];

    public $tableName;

    public $aliasTableName;

    public $tableAliasName;

    public $primaryKeyName;

    public $sequenceName;

    public $primaryKeyValue;

    public $fields = [];

    public $timestampFields = [];

    public $attributes = [];

    public $selectFields = ['*'];

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

    public $joinModel;

    public $joinOn = '';

    public $bindcount = 0;

    public function __construct($pdo = '', $attributes = [])
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }
        $this->keyName = $this->primaryKeyName;
    }

    public function __invoke($pdo = null)
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        if ('gets' === $name) {
            return $this->buildGets($name, $arguments);
        } elseif ('get' === $name) {
            return $this->buildGet($name, $arguments);
        } elseif (0 === \strpos($name, 'orderBy')) {
            return $this->buildOrderBy($name, $arguments);
        } elseif (0 === \strpos($name, 'where')) {
            return $this->buildWhere($name, $arguments);
        } elseif (0 === \strpos($name, 'and')) {
            return $this->buildAnd($name, $arguments);
        } elseif (0 === \strpos($name, 'or')) {
            return $this->buildOr($name, $arguments);
        } elseif (0 === \strpos($name, 'key')) {
            return $this->buildKey($name, $arguments);
        } elseif (0 === \strpos($name, 'alias')) {
            return $this->buildAlias($name, $arguments);
        } elseif (0 === \strpos($name, 'match')) {
            return $this->buildMatch($name, $arguments);
        } elseif (0 === \strpos($name, 'getBy')) {
            return $this->buildGetBy($name, $arguments);
        } elseif (0 === \strpos($name, 'getCount')) {
            return $this->buildGetCount($name, $arguments);
        } elseif (0 === \strpos($name, 'getsBy')) {
            return $this->buildGetsBy($name, $arguments);
        } elseif (0 === \strpos($name, 'set')) {
            return $this->buildSet($name, $arguments);
        } elseif (0 === \strpos($name, 'get')) { // get field
            return $this->buildGetField($name, $arguments);
        } elseif (0 === \strpos($name, 'gt')) {
            return $this->buildGt($name, $arguments);
        } elseif (0 === \strpos($name, 'lt')) {
            return $this->buildLt($name, $arguments);
        } elseif (0 === \strpos($name, 'ge')) {
            return $this->buildGe($name, $arguments);
        } elseif (0 === \strpos($name, 'le')) {
            return $this->buildLe($name, $arguments);
        } elseif (0 === \strpos($name, 'eq')) {
            return $this->buildEq($name, $arguments);
        } elseif (0 === \strpos($name, 'ne')) {
            return $this->buildNe($name, $arguments);
        } elseif (0 === \strpos($name, 'lk')) {
            return $this->buildLk($name, $arguments);
        }

        throw new \Limepie\Exception('"' . $name . '" method not found', 1999);
    }

    public function __debugInfo()
    {
        return $this->attributes;
    }

    public function setAttribute($field, $attribute)
    {
        $this->attributes[$field] = $attribute;
    }

    public function setAttributeses(array $attributes = [])
    {
        $class = \get_called_class();

        foreach ($attributes as $attribute) {
            $this->attributes[] = new $class($this->pdo, $attribute);
        }

        return $this;
    }

    public function setAttributes(array $attributes = [])
    {
        if ($attributes) {
            $type = 0;

            // 정해진 필드만
            if (1 === $type) {
                foreach ($this->allFields as $field) {
                    if (true === isset($attributes[$field])) {
                        $this->attributes[$field] = $attributes[$field];
                    } elseif (true === isset($attributes[$this->tableName . '_' . $field])) {
                        $field1                   = $this->tableName . '_' . $field;
                        $this->attributes[$field] = $attributes[$field1];
                    } else {
                        $this->attributes[$field] = null;
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
            foreach ($attributes as $field => &$value) {
                if (true === isset($this->dataTypes[$field])) {
                    switch ($this->dataTypes[$field]) {
                        case 'serialize':
                            if ($value) {
                                try {
                                    $org   = $value;
                                    $value = \Limepie\unserialize($value);
                                } catch (\Exception $e) {
                                    throw new \Exception($this->tableName . ' ' . $field . ' "...' . \mb_substr($org, -10, 10) . '" unserialize error');
                                }
                            } else {
                                $value = [];
                            }

                            break;
                        case 'json':
                            if ($value) {
                                $value = \json_decode($value, true);
                            } else {
                                $value = [];
                            }

                            break;
                        case 'yml':
                        case 'yaml':
                            if ($value) {
                                $value = \yaml_parse($value);
                            } else {
                                $value = [];
                            }

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
                    throw new \Limepie\Exception('relation left ' . $this->tableName . ' "' . $leftKeyName . '" field not found');
                }
                $args = [$attributes[$leftKeyName]];

                foreach ($class->and as $key => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key);
                    $args[] = $value;
                }

                $connect = $class->getConnectOrNull();

                if (!$connect) {
                    $connect = $this->getConnect();
                }

                $class->keyName = $rightKeyName;
                //pr([$class($connect), $functionName], var_dump($args));
                $data = \call_user_func_array([$class($connect), $functionName], $args);

                if ($class->aliasTableName) {
                    $attributes[$class->aliasTableName] = $data;
                } else {
                    $attributes[$class->tableName . '_model'] = $data;
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
                $args         = [$attributes[$leftKeyName]];

                foreach ($class->and as $key1 => $value) {
                    $functionName .= 'And' . \Limepie\camelize($key1);
                    $args[] = $value;
                }

                $connect = $class->getConnectOrNull();

                if (!$connect) {
                    $connect = $this->getConnect();
                }

                $data = \call_user_func_array([$class($connect), $functionName], $args);

                if ($class->aliasTableName) {
                    $attributes[$class->aliasTableName] = $data;
                } else {
                    $attributes[$class->tableName . '_models'] = $data;
                }
            }
        }

        return $attributes;
    }

    public function getRelations($attributes)
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                if ($class->aliasTableName) {
                    $moduleName = $class->aliasTableName;
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
                    //pr($row);
                    if (true === \array_key_exists($leftKeyName, $row->toArray())) {
                        //if (true === isset($row[$leftKeyName])) {
                        $seqs[] = $row[$leftKeyName];
                    } else {
                        throw new \Limepie\Exception($class->tableName . ' table ' . $leftKeyName . ' field not found');
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

                    if ($data) {
                        foreach ($attributes as $attribute) {
                            $attr = $attribute[$leftKeyName] ?? '';

                            if ($attr && true === isset($data[$attr])) {
                                $attribute->offsetSet($moduleName, $data[$attr]);
                            } else {
                                $attribute->offsetSet($moduleName, []);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, []);
                        }
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        $attribute->offsetSet($moduleName, []);
                    }
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                if ($class->aliasTableName) {
                    $moduleName = $class->aliasTableName;
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
                    $seqs[] = $attribute[$leftKeyName];
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
                        foreach ($attributes as $attribute) {
                            $attr = $attribute[$leftKeyName] ?? '';

                            if ($attr && true === isset($group[$attr])) {
                                if ($class->keyName === $remapKey) {
                                    $attribute->offsetSet($moduleName, new $class($this->getConnect(), $group[$attr]));
                                } else {
                                    // ->key로 바꿈
                                    $new = [];

                                    foreach ($group[$attr] as $key => $value) {
                                        if (false === \in_array($remapKey, $value->allFields, true)) {
                                            throw new \Limepie\Exception($remapKey . ' field not found');
                                        }
                                        //if (false === isset($value[$remapKey])) {
                                        if (false === \array_key_exists($remapKey, $value->attributes)) {
                                            // 키가 존재하지 않을 경우 에러를 낼것인가. 배열을 만들지 않을것인가?
                                            // 결정 #1
                                            // 컬럼의 값이 널일경우 매칭을 안시키면 되는데 에러가 나므로 해당 코드를 건너뛸수가 없음.
                                            // 대상 키가 널이면 새로 만들어질 배열에 포함시키지 않는다.
                                            // 만약 문제가 생긴다면 모델의 옵션 지정을 통해 에러 또는 건너뜀을 선택하여야 함.
                                            // 변경 #1
                                            // 데이터는 문제가 있는 것을 모델에서 회피함으로서 다른 문제가 발생한다.
                                            // 모델 class에서 처리할수 없는 상황들이 있으므로 데이터를 교정하여야 한다는 결론.
                                            // 에러 내는것으로 변경함.
                                            throw new \Limepie\Exception($remapKey . ' field is null, not match');
                                            // $new[$value[$remapKey]] = $value;
                                        }
                                        $new[$value[$remapKey]] = $value;
                                    }
                                    $attribute->offsetSet($moduleName, new $class($this->getConnect(), $new));
                                }
                            } else {
                                $attribute->offsetSet($moduleName, []);
                            }
                        }
                    } else {
                        foreach ($attributes as $attribute) {
                            $attribute->offsetSet($moduleName, []);
                        }
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        $attribute->offsetSet($moduleName, []);
                    }
                }
            }
        }

        return $attributes;
    }

    public function getConditionAndBinds($whereKey, $arguments)
    {
        $condition = '';
        $binds     = [];

        //pr($whereKey, $arguments);
        if (false !== \strpos($whereKey, '_and_')) {
            $whereKeys = \explode('_and_', $whereKey);
            $conds     = [];
            $binds     = [];

            foreach ($whereKeys as $index => $key) {
                if ($arguments[$index] && true === \is_array($arguments[$index])) {
                    $_conditions = [];

                    foreach ($arguments[$index] as $index2 => $value2) {
                        $_conditions[]               = ":{$key}{$index2}";
                        $binds[':' . $key . $index2] = $value2;
                    }
                    $conds[] = "`{$this->tableAliasName}`.`{$key}` IN (" . \implode(', ', $_conditions) . ')';
                } else {
                    $key2 = \substr($key, 3);

                    if (0 === \strpos($key, 'gt_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' > :' . $key;
                    } elseif (0 === \strpos($key, 'lt_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' < :' . $key;
                    } elseif (0 === \strpos($key, 'ge_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' >= :' . $key;
                    } elseif (0 === \strpos($key, 'le_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' <= :' . $key;
                    } elseif (0 === \strpos($key, 'eq_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' = :' . $key;
                    } elseif (0 === \strpos($key, 'ne_')) {
                        if (null === $arguments[$index]) {
                            $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' IS NOT NULL';
                        } else {
                            $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' != :' . $key;
                        }
                    } elseif (0 === \strpos($key, 'lk_')) {
                        $conds[] = "`{$this->tableAliasName}`." . '`' . $key2 . '`' . ' like concat("%", :' . $key . ', "%")';
                    } else {
                        // $this->bindcount++;

                        // $this->conditions[] = [
                        //     'string' => $key. ' = :'.$key.$this->bindcount ,
                        //     'bind' => [
                        //         $key.$this->bindcount => $arguments[$index]
                        //     ]
                        // ];
                        if (null === $arguments[$index]) {
                            $conds[] = "`{$this->tableAliasName}`." . '`' . $key . '` IS NULL';
                        } else {
                            $conds[] = "`{$this->tableAliasName}`." . '`' . $key . '`' . ' = :' . $key;
                        }
                    }

                    if (null === $arguments[$index]) {
                    } else {
                        $binds[':' . $key] = $arguments[$index];
                    }
                }
            }
            $condition = \implode(' AND ', $conds);

        //pr($condition);
        } elseif (true === isset($arguments[0]) || ($arguments && null === $arguments[0])) {
            $whereValue = $arguments[0];

            if (true === \is_array($whereValue)) {
                $conditions = [];
                $binds      = [];

                foreach ($whereValue as $index => $value) {
                    $conditions[]                    = ":{$whereKey}{$index}";
                    $binds[':' . $whereKey . $index] = $value;
                }
                $condition = "`{$this->tableAliasName}`.`{$whereKey}` IN (" . \implode(', ', $conditions) . ')';
            } else {
                $whereKey2 = \substr($whereKey, 3);

                if (0 === \strpos($whereKey, 'gt_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` > :{$whereKey}";
                } elseif (0 === \strpos($whereKey, 'lt_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` < :{$whereKey}";
                } elseif (0 === \strpos($whereKey, 'ge_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` >= :{$whereKey}";
                } elseif (0 === \strpos($whereKey, 'le_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` <= :{$whereKey}";
                } elseif (0 === \strpos($whereKey, 'eq_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` = :{$whereKey}";
                } elseif (0 === \strpos($whereKey, 'ne_')) {
                    if (null === $whereValue) {
                        $condition = "`{$this->tableAliasName}`.`{$whereKey2}` IS NOT NULL";
                    } else {
                        $condition = "`{$this->tableAliasName}`.`{$whereKey2}` != :{$whereKey}";
                    }
                } elseif (0 === \strpos($whereKey, 'lk_')) {
                    $condition = "`{$this->tableAliasName}`.`{$whereKey2}` like concat('%',:{$whereKey},'%')";
                } else {
                    if (null === $whereValue) {
                        $condition = "`{$this->tableAliasName}`.`{$whereKey}` IS NULL";
                    } else {
                        $condition = "`{$this->tableAliasName}`.`{$whereKey}` = :{$whereKey}";
                    }
                }

                if (null === $whereValue) {
                    $binds = [];
                } else {
                    $binds = [
                        ':' . $whereKey => $whereValue,
                    ];
                }
            }
        }
        //pr($this);
        //\pr(func_get_args());

        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }

        return [$condition, $binds];
    }

    public function match($leftKeyName, $rightKeyName)
    {
        $this->leftKeyName  = $leftKeyName;
        $this->rightKeyName = $rightKeyName;

        return $this;
    }

    public function and($key, $value = null)
    {
        if ($key instanceof \Closure) {
            //\pr($key($this));
        } else {
            $this->and[$key] = $value;
        }

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
        //pr([new $class]);
        $this->oneToOne[] = $class;

        return $this;
    }

    public function oneToMany($class)
    {
        //pr([new $class]);
        $this->oneToMany[] = $class;

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

    public function setConnect($connect)
    {
        return $this->pdo = $connect;
    }

    public function count()
    {
        return \count($this->attributes);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        if (false === \array_key_exists($offset, $this->attributes)) {
            $traces = \debug_backtrace();

            foreach ($traces as $trace) {
                if (true === isset($trace['file'])) {
                    if (false === \strpos($trace['file'], '/limepie-framework/src/')) {
                        //if($trace['function'] == '__call') continue;

                        if (false === \in_array($offset, $this->allFields, true)) {
                            $message = $offset . ' not found';
                            $code    = '234';
                        } else {
                            $message = $offset . ' is null';
                            $code    = '123';
                        }
                        $filename = $trace['file'];
                        $line     = $trace['line'];

                        if (true === \Limepie\is_cli()) {
                            $message = "{$code}: {$message} in {$filename} on line {$line}";
                        } elseif (true === \Limepie\is_ajax()) {
                            $message = \json_encode([
                                'message' => "{$code}: {$message} in {$filename} on line {$line}",
                            ], \JSON_UNESCAPED_UNICODE);
                        } else {
                            $message = "{$code}: {$message} in <b>{$filename}</b> on line <b>{$line}</b>\n\n";
                        }

                        break;
                    }
                }
            }

            throw new \Limepie\Exception($message);
        }

        return $this->attributes[$offset];
        //return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    public function objectToArray()
    {
        if (true === isset($this->attributes[$this->primaryKeyName])) {
            return $this->iteratorToArray($this->attributes);
        }

        $attributes = [];

        foreach ($this->attributes as $index => $attribute) {
            $attributes[$index] = $this->iteratorToArray($attribute);
        }

        return $attributes;
    }

    public function toArray(\Closure $callback = null)
    {
        $attributes = $this->objectToArray();

        if (true === isset($callback) && $callback) {
            return $callback($attributes);
        }

        return $attributes;
    }

    public function filter(\Closure $callback = null)
    {
        if (true === isset($callback) && $callback) {
            return $callback($this);
        }

        //return $attributes;
    }

    public function rewind()
    {
        \reset($this->attributes);
    }

    public function current()
    {
        $var = \current($this->attributes);

        return $var;
    }

    public function key($keyName = null)
    {
        if ($keyName) {
            return $this->keyName($keyName);
        }

        $var = \key($this->attributes);

        return $var;
    }

    public function next()
    {
        $var = \next($this->attributes);

        return $var;
    }

    public function valid()
    {
        $key = \key($this->attributes);
        $var = (null !== $key && false !== $key);

        return $var;
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
        $fields = [];
        $binds  = [];
        $values = [];

        foreach ($this->allFields as $field) {
            if ($this->sequenceName === $field) {
            } else {
                if ('created_ts' === $field || 'updated_ts' === $field) {
                } elseif ('ip' === $field) {
                    $fields[]            = '`' . $field . '`';
                    $binds[':' . $field] = \Limepie\getIp();
                    $values[]            = 'inet6_aton(:' . $field . ')';
                } elseif (true === \array_key_exists($field, $this->attributes)) {
                    //if (true === isset($this->attributes[$field])) {
                    $value = $this->attributes[$field];

                    if (true === isset($this->dataTypes[$field])) {
                        switch ($this->dataTypes[$field]) {
                            case 'serialize':
                                $value = \serialize(\json_decode(\json_encode($value), true));

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

                    // if (true === \is_array($value)) {
                    //     $fields[] = '`' . $field . '`';
                    //     $values[] = $value[0];

                    //     foreach ($value[1] as $vKey => $vValue) {
                    //         $binds[':' . $vKey] = $vValue;
                    //     }
                    // } else {
                    $fields[]            = '`' . $field . '`';
                    $binds[':' . $field] = $value;
                    $values[]            = ':' . $field;
                    // }
                }
            }
        }
        $field  = \implode(', ', $fields);
        $values = \implode(', ', $values);
        $sql    = <<<SQL
            INSERT INTO
                `{$this->tableName}`
            ({$field})
                VALUES
            ({$values})
        SQL;

        $result     = false;
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
        $fields = [];

        $binds = [
            ':' . $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
        ];

        foreach ($this->allFields as $field) {
            if ($this->sequenceName === $field) {
            } else {
                if ('created_ts' === $field || 'updated_ts' === $field) {
                } elseif ('ip' === $field) {
                    $fields[]            = "`{$this->tableName}`." . '`' . $field . '` = inet6_aton(:' . $field . ')';
                    $binds[':' . $field] = \Limepie\getIp();
                } elseif (true === \array_key_exists($field, $this->attributes)) {
                    //if (true === isset($this->attributes[$field])) {
                    $value = $this->attributes[$field];

                    if (true === isset($this->dataTypes[$field])) {
                        switch ($this->dataTypes[$field]) {
                            case 'serialize':
                                $value = \serialize(\json_decode(\json_encode($value), true));

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

                    // if (true === \is_array($value)) {
                    //     $fields[] = "`{$this->tableName}`." . '`' . $field . '` = ' . $value[0];

                    //     foreach ($value[1] as $vKey => $vValue) {
                    //         $binds[':' . $vKey] = $vValue;
                    //     }
                    // } else {
                    $fields[]            = "`{$this->tableName}`." . '`' . $field . '` = :' . $field;
                    $binds[':' . $field] = $value;
                    // }
                }
            }
        }
        $field = \implode(', ', $fields);
        $where = $this->primaryKeyName;
        $sql   = <<<SQL
            UPDATE
                `{$this->tableName}`
            SET
                {$field}
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

            if ($debug) {
                \Limepie\pr($sql, $binds);
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

            if ($debug) {
                \Limepie\pr($sql, $binds);
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

    public function getSelectFields()
    {
        $fields = [];

        foreach ($this->selectFields as $column => $alias) {
            if (true === \is_numeric($column)) {
                if ('ip' === $alias) {
                    $fields[] = "inet6_ntoa(`{$this->tableAliasName}`." . '`' . $alias . '`) `' . $alias . '`';
                } else {
                    $fields[] = "`{$this->tableAliasName}`." . '`' . $alias . '`';
                }
            } else {
                $fields[] = $column . ' AS `' . $alias . '`';
            }
        }

        return \implode(', ', $fields);
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

    public function addColumn($column, $aliasName)
    {
        $this->selectFields[$column] = $aliasName;

        return $this;
    }

    public function columns($columns)
    {
        $this->selectFields = $columns;

        return $this;
    }

    public function addColumns($columns)
    {
        $this->selectFields += $columns;

        return $this;
    }

    public function keyName($keyName)
    {
        $this->keyName = $keyName;

        return $this;
    }

    public function join($model)
    {
        $this->joinModel = $model;

        return $this;
    }

    public function on($leftKeyName, $rightKeyName)
    {
        $this->joinOn = [$leftKeyName, $rightKeyName];

        return $this;
    }

    public function alias($tableName)
    {
        $this->aliasTableName = $tableName;

        return $this;
    }

    public function debug()
    {
        \pr($this->query, $this->binds);
        //exit;
    }

    public function buildGetCount($name, $arguments)
    {
        $whereKey            = \Limepie\decamelize(\substr($name, 10));
        [$condition, $binds] = $this->getConditionAndBinds($whereKey, $arguments);
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

    private function buildSet($name, $arguments)
    {
        $fieldName = \Limepie\decamelize(\substr($name, 3));

        if (false === \in_array($fieldName, $this->allFields, true)) {
            throw new \Limepie\Exception('set ' . $this->tableName . ' "' . $fieldName . '" field not found');
        }

        $this->attributes[$fieldName] = $arguments[0];

        return $this;
    }

    private function buildGets($name, $arguments)
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';
        $selectFields          = $this->getSelectFields();
        $orderBy               = $this->getOrderBy($arguments[0]['order'] ?? null);
        $limit                 = $this->getLimit();
        $condition             = '';
        $binds                 = [];
        $join                  = '';

        if (true === isset($arguments[0]['condition'])) {
            $condition = '    WHERE ' . $arguments[0]['condition'];
        } else {
            if ($this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }
        }

        if (true === isset($arguments[0]['binds'])) {
            $binds = $arguments[0]['binds'];
        }

        if (!$condition && $this->condition) {
            $condition = '' . $this->condition;
            $binds     = $this->binds;
        }

        $sql = <<<SQL
            SELECT
                {$selectFields}
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
        $data            = $this->getConnect()->gets($sql, $binds);

        $class = \get_called_class();

        $attributes = [];

        foreach ($data as $index => $row) {
            if (false === \array_key_exists($this->keyName, $row)) {
                throw new \Limepie\Exception('gets ' . $this->tableName . ' "' . $this->keyName . '" field not found');
            }

            $attributes[$row[$this->keyName]] = new $class($this->getConnect(), $row);
        }

        if ($attributes) {
            $attributes       = $this->getRelations($attributes);
            $this->attributes = $attributes;

            return $this;
        }

        return [];
    }

    private function buildGet($name, $arguments)
    {
        $selectFields = $this->getSelectFields();
        $condition    = '';
        $binds        = [];
        $orderBy      = $this->getOrderBy();
        // TODO: buildGets와 같이 정리 필요

        if (true === isset($arguments[0]['condition'])) { // not use
            $condition = 'WHERE ' . $arguments[0]['condition'];
            $binds     = $arguments[0]['binds'];
        } else {
            if ($this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }
        }

        $sql = <<<SQL
            SELECT
                {$selectFields}
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$condition}
            {$orderBy}
            LIMIT 1
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        $attributes = $this->getConnect()->get($sql, $binds);

        if ($attributes) {
            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }
    }

    private function buildOrderBy($name, $arguments)
    {
        if (1 === \preg_match('#orderBy(?P<field>.*)(?P<how>Asc|Desc)#', $name, $m)) {
            $this->orderBy = \Limepie\decamelize($m['field']) . ' ' . \strtoupper($m['how']);
        } elseif (1 === \preg_match('#orderBy(?P<field>.*)#', $name, $m)) {
            $this->orderBy = \Limepie\decamelize($m['field']) . ' ASC';
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildWhere($name, $arguments)
    {
        $whereKey = \Limepie\decamelize(\substr($name, 7));

        [$this->condition, $this->binds] = $this->getConditionAndBinds($whereKey, $arguments);

        return $this;
    }

    private function buildAnd($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 3));

        $this->and[$key] = $arguments[0];

        $this->bindcount++;

        $this->conditions[] = [
            'string' => 'and',
        ];

        $whereKey = \substr($key, 3);

        if (0 === \strpos($key, 'gt_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' > :' . $whereKey . $this->bindcount,
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } elseif (0 === \strpos($key, 'lt_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' < :' . $whereKey . $this->bindcount,
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } elseif (0 === \strpos($key, 'ge_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' >= :' . $whereKey . $this->bindcount,
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } elseif (0 === \strpos($whereKey, 'le_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' <= :' . $whereKey . $this->bindcount,
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } elseif (0 === \strpos($whereKey, 'eq_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' = :' . $whereKey . $this->bindcount,
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } elseif (0 === \strpos($whereKey, 'ne_')) {
            $this->bindcount++;

            if (null === $arguments[0]) {
                $this->conditions[] = [
                    'string' => $whereKey . ' IS NOT NULL',
                ];
            } else {
                $this->conditions[] = [
                    'string' => $whereKey . ' != :' . $whereKey . $this->bindcount,
                    'bind'   => [
                        $whereKey . $this->bindcount => $arguments[0],
                    ],
                ];
            }
        } elseif (0 === \strpos($whereKey, 'lk_')) {
            $this->bindcount++;

            $this->conditions[] = [
                'string' => $whereKey . ' like concat("%", :' . $whereKey . $this->bindcount . ', "%")',
                'bind'   => [
                    $whereKey . $this->bindcount => $arguments[0],
                ],
            ];
        } else {
            $this->bindcount++;

            if (null === $arguments[0]) {
                $this->conditions[] = [
                    'string' => $key . ' IS NULL',
                ];
            } else {
                $this->conditions[] = [
                    'string' => $key . ' = :' . $key . $this->bindcount,
                    'bind'   => [
                        $key . $this->bindcount => $arguments[0],
                    ],
                ];
            }
        }

        return $this;
    }

    private function buildGe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        $this->bindcount++;

        $this->conditions[] = [
            'string' => $key . ' >= :' . $key . $this->bindcount,
            'bind'   => [
                $key . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildLe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        $this->bindcount++;

        $this->conditions[] = [
            'string' => $key . ' <= :' . $key . $this->bindcount,
            'bind'   => [
                $key . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildNe($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        $this->bindcount++;

        if (null === $arguments[0]) {
            $this->conditions[] = [
                'string' => $key . ' IS NOT NULL',
                'bind'   => [
                ],
            ];
        } else {
            $this->conditions[] = [
                'string' => $key . ' != :' . $key . $this->bindcount,
                'bind'   => [
                    $key . $this->bindcount => $arguments[0],
                ],
            ];
        }

        return $this;
    }

    private function buildLk($name, $arguments)
    {
        $key = \Limepie\decamelize(\substr($name, 2));

        $this->bindcount++;

        $this->conditions[] = [
            'string' => $key . ' like concat("%", :' . $key . $this->bindcount, ', "%")',
            'bind'   => [
                $key . $this->bindcount => $arguments[0],
            ],
        ];

        return $this;
    }

    private function buildKey($name, $arguments)
    {
        $this->keyName = \Limepie\decamelize(\substr($name, 3));

        return $this;
    }

    private function buildAlias($name, $arguments)
    {
        $this->aliasTableName = \Limepie\decamelize(\substr($name, 5));

        return $this;
    }

    private function buildMatch($name, $arguments)
    {
        if (1 === \preg_match('#match(?P<leftKeyName>.*)With(?P<rightKeyName>.*)#', $name, $m)) {
            $this->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $this->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new \Limepie\Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildGetBy($name, $arguments)
    {
        $this->attributes    = [];
        $whereKey            = \Limepie\decamelize(\substr($name, 5));
        [$condition, $binds] = $this->getConditionAndBinds($whereKey, $arguments);
        $selectFields        = $this->getSelectFields();
        $orderBy             = $this->getOrderBy();
        $limit               = $this->getLimit();
        $sql                 = <<<SQL
            SELECT
                {$selectFields}
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$condition}
            {$orderBy}
            {$limit}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if ($this->getConnect() instanceof \Pdo) {
            $attributes = $this->getConnect()->get($sql, $binds);
        } else {
            throw new \Limepie\Exception('lost connection');
        }

        if ($attributes) {
            $attributes            = $this->buildDataType($attributes);
            $this->attributes      = $this->getRelation($attributes);
            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return [];
    }

    private function buildGetsBy($name, $arguments)
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';
        $whereKey              = \Limepie\decamelize(\substr($name, 6));
        [$condition, $binds]   = $this->getConditionAndBinds($whereKey, $arguments);
        $selectFields          = $this->getSelectFields();
        $orderBy               = $this->getOrderBy();
        $limit                 = $this->getLimit();
        $join                  = '';

        if ($this->joinModel) {
            $join = 'INNER JOIN ' . $this->joinModel->tableName . ' ON ' . $this->tableName . '.' . $this->joinModel->joinOn[0] . ' = ' . $this->joinModel->tableName . '.' . $this->joinModel->joinOn[1];

            $selectFields = [];

            if (true) { // join하는 테이블의 필드 우선
                foreach ($this->joinModel->selectFields as $field) {
                    $selectFields[] = $this->joinModel->tableName . '.' . $field;
                }

                foreach ($this->selectFields as $field) {
                    if (false === \in_array($field, $this->joinModel->selectFields, true)) {
                        $selectFields[] = $this->tableName . '.' . $field;
                    }
                }
            } else {
                foreach ($this->selectFields as $field) {
                    $selectFields[] = $this->tableName . '.' . $field;
                }

                foreach ($this->joinModel->selectFields as $field) {
                    if (false === \in_array($field, $this->selectFields, true)) {
                        $selectFields[] = $this->joinModel->tableName . '.' . $field;
                    }
                }
            }

            $selectFields = \implode(',', $selectFields);
        }

        $sql = <<<SQL
            SELECT
                {$selectFields}
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

        $data       = $this->getConnect()->gets($sql, $binds);
        $attributes = [];

        $class = \get_called_class();

        foreach ($data as $index => $row) {
            if (false === \array_key_exists($this->keyName, $row)) {
                throw new \Limepie\Exception('gets by ' . $this->tableName . ' "' . $this->keyName . '" field not found');
            }
            $attributes[$row[$this->keyName]] = new $class($this->getConnect(), $row);
        }

        if ($attributes) {
            $this->attributes = $this->getRelations($attributes);

            return $this;
        }

        return [];
    }

    private function buildGetField($name, $arguments)
    {
        // field name
        $isOrEmpty = false;
        $isOrNull  = false;

        if (false !== \strpos($name, 'OrNull')) {
            $isOrNull  = true;
            $fieldName = \Limepie\decamelize(\substr($name, 3, -6));
        } elseif (false !== \strpos($name, 'OrEmpty')) {
            $isOrEmpty = true;
            $fieldName = \Limepie\decamelize(\substr($name, 3, -7));
        } else {
            $fieldName = \Limepie\decamelize(\substr($name, 3));
        }

        if (!$name) {
            throw new \Limepie\Exception('get ' . $this->tableName . ' "' . $fieldName . '" field not found', 999);
        }

        if (true === isset($this->attributes[$fieldName])) {
            return $this->attributes[$fieldName];
        } elseif (true === $isOrEmpty) {
            if (false === \in_array($fieldName, $this->allFields, true)) { // model
                return [];
            }

            return ''; // column
        }

        if (false === $isOrNull && false === $isOrEmpty) {
            // unknown column
            if (false === \in_array($fieldName, $this->allFields, true)) {
                // $traces = debug_backtrace();
                // $msg = '';
                // foreach($traces as $trace) {
                //     if(false !== strpos($trace['class'], 'Limepie\Model')) {
                //         $msg =
                //     }
                // }

                throw new \Limepie\Exception('get ' . $this->tableName . ' "' . $fieldName . '" field not found', 1999);
            }
        }

        return null;
    }

    private function iteratorToArray($attributes)
    {
        $data = [];

        foreach ($attributes as $key => $attribute) {
            if ($attribute instanceof self) {
                $data[ $key ] = $attribute->objectToArray();
            } else {
                if (true === \is_array($attribute)) {
                    if (0 < \count($attribute)) {
                        foreach ($attribute as $k2 => $v2) {
                            if (true === \is_object($v2)) {
                                $data[ $key ][$k2] = $v2->objectToArray();
                            } else {
                                $data[ $key ][$k2] = $v2;
                            }
                        }
                    } else {
                        $data [ $key ] = [];
                    }
                } else {
                    $data[ $key ] = $attribute;
                }
            }
        }

        return $data;
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
}
