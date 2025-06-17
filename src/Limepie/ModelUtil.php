<?php

declare(strict_types=1);

namespace Limepie;

class ModelUtil extends ModelBase
{
    protected function getSelectColumns(string $prefixString = '', $isCount = false) : string
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
                $rand = \Limepie\genRandomString();

                if (true === \is_numeric($column)) {
                    if (false == isset($this->dataStyles[$alias])) {
                        \pr($this->tableName, $alias, $this->selectColumns);
                    }

                    if ('ip' === $alias) {
                        $columns[] = "inet6_ntoa(`{$this->tableAliasName}`." . '`' . $alias . '`) AS `' . $prefix . $alias . '`';
                    } elseif ('aes_serialize' === $this->dataStyles[$alias]) {
                        $columns[] = "AES_DECRYPT(`{$this->tableAliasName}`." . '`' . $alias . '`, :' . $prefix . $alias . '_secretkey) AS `' . $prefix . $alias . '`';

                        if (false === $isCount) {
                            $this->binds[':' . $prefix . $alias . '_secretkey'] = Aes::$salt;
                        }
                    } elseif ('aes' === $this->dataStyles[$alias]) {
                        $columns[] = "AES_DECRYPT(`{$this->tableAliasName}`." . '`' . $alias . '`, :' . $prefix . $alias . '_secretkey) AS `' . $prefix . $alias . '`';

                        if (false === $isCount) {
                            $this->binds[':' . $prefix . $alias . '_secretkey'] = Aes::$salt;
                        }
                    } elseif ('aes_hex' === $this->dataStyles[$alias]) {
                        $columns[] = "AES_DECRYPT(UNHEX(`{$this->tableAliasName}`." . '`' . $alias . '`), :' . $prefix . $alias . '_secretkey_' . $rand . ') AS `' . $prefix . $alias . '`';

                        if (false === $isCount) {
                            $this->binds[':' . $prefix . $alias . '_secretkey_' . $rand . ''] = Aes::$salt;
                        }
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
                        $alias       = (object) $alias;
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
                                throw new Exception($e);
                            }
                        } else {
                            if (isset($this->dataStyles[$alias->columnName]) && 'aes_hex' === $this->dataStyles[$alias->columnName]) {
                                $columns[] = "AES_DECRYPT(UNHEX(`{$this->tableAliasName}`." . '`' . $alias->columnName . '`), :' . $prefix . $alias->aliasName . '_secretkey_' . $rand . ') AS `' . $prefix . $alias->aliasName . '`';

                                if (false === $isCount) {
                                    $this->binds[':' . $prefix . $alias->aliasName . '_secretkey_' . $rand . ''] = Aes::$salt;
                                }
                            } elseif (false === \strpos($alias->columnName, '(')) {
                                $columns[] = "`{$this->tableAliasName}`." . '`' . $alias->columnName . '`' . $aliasString;
                            } else {
                                $columns[] = $alias->columnName . $aliasString;
                            }
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
                                throw new Exception($e);
                            }
                        } else {
                            $columns[] = "`{$this->tableAliasName}`." . '`' . $column . '`' . $aliasString;
                        }
                    } else {
                        $aliasString = (true === isset($alias) && $alias ? ' AS `' . ($prefix ? $prefix : '') . $alias . '`' : '');

                        if (false === \strpos($column, '(')) {
                            $columns[] = "`{$this->tableAliasName}`." . '`' . $column . '`' . $aliasString;
                        } else {
                            $columns[] = $column . $aliasString;
                        }
                    }
                }
            }
        }

        $rawColumns = [];

        foreach ($this->rawColumnAliasNames as $alias => $rawColumn) {
            $rawColumns[] = $rawColumn . ' AS `' . $prefix . $alias . '`';
        }

        return \implode(PHP_EOL . '        , ', $columns)
        . ($rawColumns ? PHP_EOL . '        , ' . \implode(PHP_EOL . '        , ', $rawColumns) : '');

        // return \implode(PHP_EOL . '        , ', $columns);
    }

    public function getJoin($isCount = false) : array
    {
        $join          = '';
        $selectColumns = '';
        $index         = -1;
        $binds         = [];
        $andConditions = [];

        foreach ($this->joinModels as $joinModel) {
            $class          = $joinModel['model'];
            $tableName      = $class->tableName;
            $tableAliasName = $class->tableAliasName;
            $joinLeft       = $joinModel['left'];
            $joinRight      = $joinModel['right'];

            $join .= PHP_EOL . '    ';

            if ($joinModel['type']) {
                // if ($isCount) {
                //     continue;
                // }
                $join .= 'LEFT';
            } else {
                $join .= 'INNER';
            }

            if (isset($joinModel['target']) && $joinModel['target']) {
                $targetTableName = $joinModel['target']->tableAliasName;
            } else {
                $targetTableName = $this->tableAliasName;
            }

            $join .= ' JOIN'
                   . PHP_EOL . '         `' . $tableName . '` AS `' . $tableAliasName . '`'
                   . PHP_EOL . '    ON'
                   . PHP_EOL . '        `' . $targetTableName . '`.`' . $joinLeft . '` = `' . $tableAliasName . '`.`' . $joinRight . '`';

            if ($class->onCondition) {
                $join .= ' AND ' . $class->onCondition;
                $binds += $class->onConditionBinds;
            }
            // \prx($class->onCondition);

            $join .= ' ' . \implode(', ', $class->forceIndexes);

            $selectColumns .= PHP_EOL . '        , ' . $class->getSelectColumns($tableAliasName, isCount: $isCount);

            if ($class->binds) {
                $binds += $class->binds;
            }

            if ($class->condition) {
                $andConditions[] = $class->condition;
            }
            $prevModel = $class;
        }

        $condition = \implode(PHP_EOL . ' ', $andConditions);

        return [
            'binds'         => $binds,
            'condition'     => $condition,
            'join'          => $join,
            'selectColumns' => $selectColumns,
            'sumColumn'     => $class->sumColumn,
            'avgColumn'     => $class->avgColumn,
            'orderBy'       => $class->orderBy,
        ];
    }

    protected function splitKey(string $name, int $offset = 0) : array
    {
        $orgKey = \substr($name, $offset);

        $whereKey = \trim(\Limepie\decamelize($orgKey), '_ ');
        // $whereKey = \trim(\Limepie\decamelize('ServiceSeqAnd((IsCloseOrIsDelete)OrIsOk)AndUserSeq'), '_ ');
        // $whereKey = \trim(\Limepie\decamelize('"myshop" regexp concat("^", `path`)'), '_ ');

        if ($whereKey) {
            $matches   = \preg_split('#([^_]+])?(_and_|_or_)([^_]+])?#U', $whereKey, flags: \PREG_SPLIT_OFFSET_CAPTURE);
            $splitKeys = [];
            $prevMatch = [];
            $offset    = 0;

            foreach ($matches as $i => $match) {
                if ($prevMatch) {
                    $operator = \strtoupper(\trim(\str_replace($prevMatch[0], '', \substr($whereKey, $offset, $match[1] - $offset)), '_'));
                    // \pr($operator, $prevMatch[0]);
                    $splitKeys[] = [
                        \str_repeat('(', \substr_count($prevMatch[0], '(_')), // open
                        \trim($prevMatch[0], '()_'), // key
                        \str_repeat(')', \substr_count($prevMatch[0], '_)')), // close
                        $operator, // 기호
                    ];
                    $offset = $match[1];
                }
                $prevMatch = $match;
            }
            $operator = \strtoupper(\trim(\str_replace($prevMatch[0], '', \substr($whereKey, $offset, $match[1] - $offset)), '_'));
            // \pr($operator, $prevMatch[0]);

            // if (\trim($prevMatch[0], '()_') != $prevMatch[0]) {
            //     echo 'dff';
            // }
            $splitKeys[] = [
                \str_repeat('(', \substr_count($prevMatch[0], '(_')), // open
                \trim($prevMatch[0], '()_'), // key
                \str_repeat(')', \substr_count($prevMatch[0], '_)')), // close
                $operator, // 기호
            ];
            // \pr($splitKeys);

            // exit;

            return $splitKeys;
        }

        return [];
    }

    public function getConditionOperator($key, $value)
    {
        if (0 === \strpos($key, 'gt_')) {
            return ' > ';
        }

        if (0 === \strpos($key, 'lt_')) {
            return ' < ';
        }

        if (0 === \strpos($key, 'ge_')) {
            return ' >= ';
        }

        if (0 === \strpos($key, 'le_')) {
            return ' <= ';
        }

        if (0 === \strpos($key, 'eq_')) {
            return ' = ';
        }

        if (0 === \strpos($key, 'ne_')) {
            if (null === $value) {
                return ' IS NOT NULL';
            }

            return ' != ';
        }
    }

    // where, and, or등의 추가 구문을 붙이지 않고 처리
    protected function getConditions(string $name, array $arguments, int $offset = 0) : array
    {
        if (false === \strpos($name, ' ')) {
            $splitKeys = $this->splitKey($name, $offset);
            $binds     = [];
            $conds     = [];

            // \prx($name, $arguments);

            foreach ($splitKeys as $index => $splitKey) {
                ++$this->bindcount;

                [$open, $key, $close, $operator] = $splitKey;

                $bindKeyname = $this->tableAliasName . '_' . $key . '_' . $this->bindcount;

                if (true === \is_object($arguments)) {
                    throw (new Exception($this->tableName . ' ' . $key . ' argument error'))->setDebugMessage('page not found', __FILE__, __LINE__);
                }

                if (1) {
                    // orperator의 마지막이 seq로 끝날경우, 값이 숫자나 null이 아니라면 처리하지 않음
                    // 이걸하면
                    // if ($_GET['keyword'] ?? false) {
                    //     $surveyModels->and('(');
                    //     $surveyModels->conditionSeq($_GET['keyword']);
                    //     $surveyModels->orName($_GET['keyword']);
                    //     $surveyModels->condition(')');
                    // } 와 같은 경우 conditionSeq가 추가 되지 않으므로 뒤의 or이 문제가 됨
                    // if (!$this->isValidSeqArgument($key, $arguments[$index] ?? null)) {
                    //     continue;
                    // }
                }

                if (false === \array_key_exists($index, $arguments)) {
                    // \pr($name, $arguments, $offset, $open, $key, $close, $operator, $splitKey);
                    // string 자체를 query로 사용하므로 bind변수가 없다.
                    $queryString = $key;

                    // throw new \Limepie\Exception($key . ': numbers of columns of arguments do not match');
                } elseif (0 === \strpos($key, 'fulltext_boolean_')) { // fulltext의 with는 여러 컬럼을 처리
                    $fixedKey = \str_replace('_with_', "`, `{$this->tableAliasName}`.`", \substr($key, 17));

                    $queryString = "MATCH(`{$this->tableAliasName}`." . '`' . $fixedKey . '` ) AGAINST (CONCAT("+", :' . $bindKeyname . ', "*") IN BOOLEAN MODE)';

                    $binds[':' . $bindKeyname] = \str_replace(' ', ' +', \trim($arguments[$index]));
                } elseif (0 === \strpos($key, 'fulltext_')) {// fulltext의 with는 여러 컬럼을 처리
                    $fixedKey = \str_replace('_with_', "`, `{$this->tableAliasName}`.`", \substr($key, 9));

                    $queryString = "MATCH(`{$this->tableAliasName}`." . '`' . $fixedKey . '` ) AGAINST (:' . $bindKeyname . ' IN NATURAL LANGUAGE MODE)';

                    $binds[':' . $bindKeyname] = \str_replace(' ', ' +', \trim($arguments[$index]));
                } elseif (false !== \strpos($key, '_with_')) { // with는 다른 테이블을 참조하기 위해 사용함, ->andColumn1WithColumn2(table instance)
                    $tmp               = \explode('_with_', $key);
                    $fixedleftkey      = $tmp[0];
                    $conditionOperator = $this->getConditionOperator($key, $arguments[$index]);

                    if ($conditionOperator) {
                        $fixedleftkey = \substr($tmp[0], 3);
                    } else {
                        $conditionOperator = ' = ';
                    }
                    $queryString = "`{$this->tableAliasName}`." . '`' . $fixedleftkey . '` ' . $conditionOperator . ' ' . "`{$arguments[$index]->tableAliasName}`." . '`' . $tmp[1] . '`';
                } elseif (0 === \strpos($key, 'between_')) {
                    $fixedKey = \substr($key, 8);

                    $queryString = "`{$this->tableAliasName}`." . '`' . $fixedKey . '` BETWEEN :' . $bindKeyname . '_a AND :' . $bindKeyname . '_b';

                    $binds[':' . $bindKeyname . '_a'] = $arguments[$index][0];
                    $binds[':' . $bindKeyname . '_b'] = $arguments[$index][1];
                } elseif ($arguments[$index] && true === \is_array($arguments[$index])) {
                    $bindkeys = [];

                    if (0 === \strpos($key, 'ne_')) {
                        $fixedKey = \substr($key, 3);

                        foreach ($arguments[$index] as $bindindex => $bindvalue) {
                            $bindkey         = ':' . $bindKeyname . '_' . $bindindex;
                            $bindkeys[]      = $bindkey;
                            $binds[$bindkey] = $bindvalue;
                        }
                        $queryString = "`{$this->tableAliasName}`.`{$fixedKey}` NOT IN (" . \implode(', ', $bindkeys) . ')';
                    } else {
                        if (false === \in_array($key, $this->allColumns, true)) {
                            throw new Exception($this->tableName . ' table: ' . $key . ' field match error');
                        }

                        foreach ($arguments[$index] as $bindindex => $bindvalue) {
                            $bindkey         = ':' . $bindKeyname . '_' . $bindindex;
                            $bindkeys[]      = $bindkey;
                            $binds[$bindkey] = $bindvalue;
                        }
                        $queryString = "`{$this->tableAliasName}`.`{$key}` IN (" . \implode(', ', $bindkeys) . ')';
                    }
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
                            throw new Exception($this->tableName . ' ' . $key . ' argument error');
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
                    } elseif (0 === \strpos($key, 'lb_')) { // like binary
                        $queryString = $leftCondition . ' like BINARY concat("%", :' . $bindKeyname . ', "%")';
                    } elseif (0 === \strpos($key, 'lk_')) {
                        $queryString = $leftCondition . ' like concat("%", :' . $bindKeyname . ', "%")';
                    } else {
                        if (null === $arguments[$index]) {
                            $queryString = "`{$this->tableAliasName}`." . '`' . $key . '` IS NULL';
                        } else {
                            if ('ip' == $key) {
                                $queryString = "`{$this->tableAliasName}`." . '`' . $key . '` = inet6_aton(:' . $bindKeyname . ')';
                            } else {
                                if (true === isset($this->dataStyles[$key])) {
                                    if ('aes_hex' == $this->dataStyles[$key]) {
                                        $binds[':' . $bindKeyname . '_secretkey'] = Aes::$salt;
                                        unset($binds[':' . $bindKeyname]);

                                        $binds[':' . \str_replace('aes_', '', $bindKeyname)] = $whereValue;

                                        $queryString = "`{$this->tableAliasName}`." . '`' . $key . '` = HEX(AES_ENCRYPT(:' . \str_replace('aes_', '', $bindKeyname) . ', :' . $bindKeyname . '_secretkey))';
                                    } else {
                                        $queryString = "`{$this->tableAliasName}`." . '`' . $key . '` = :' . $bindKeyname;
                                    }
                                } else {
                                    throw (new Exception($key . ': Undefined'))
                                        ->setDebugMessage($this->tableName . ' undefined column ' . $key, __FILE__, __LINE__)
                                    ;
                                }
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
        } else {
            $binds = [];

            if ($offset) {
                $conds[]  = \substr($name, $offset);
                $operator = \substr($name, 0, $offset);

                // if ('condition' != $operator) {
                //     $conds[] = $operator;
                // }
            } else {
                // join시 table명을 넣은 조건을 alias table 명으로 바꿔줌.
                // ->and('DATE_ADD(service_coupon.created_ts, INTERVAL service_coupon.expire_date_count DAY) > now()')

                $conds[] = $name;
            }
        }

        return [$conds, $binds];
    }

    public function getRelation(array $attributes = [])
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                $attributes = $this->getRelationData(
                    $class,
                    $attributes,
                    functionName: 'getBy',
                    isSingle: true
                );
            }
        }

        if ($this->oneToOnes ?? false) {
            foreach ($this->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    $attributes = $this->getRelationData(
                        $class,
                        $attributes,
                        parentTableName: $parentTableName,
                        functionName: 'getBy',
                        isSingle: true
                    );
                }
            }
        }

        // if ($this->oneToOnes ?? false) {
        //     \pr($this->oneToOnes);
        // }

        // if ($this->oneToManies ?? false) {
        //     \pr($this->oneToManies);
        // }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                $attributes = $this->getRelationData(
                    $class,
                    $attributes,
                    functionName: 'getsBy',
                    isSingle: false
                );
            }
        }

        if ($this->oneToManies ?? false) {
            foreach ($this->oneToManies as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    $attributes = $this->getRelationData(
                        $class,
                        $attributes,
                        parentTableName: $parentTableName,
                        functionName: 'getsBy',
                        isSingle: false
                    );
                }
            }
        }

        return $attributes;
    }

    public function getRelationData(Model $class, $attribute, $functionName, $parentTableName = '', $isSingle = true)
    {
        if ($class->pdo) {
            $connect = $class->pdo;
        } else {
            $connect = $this->getConnect();
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

        // single일때 keyName을 바꾸는건 사실상 적용되지 않는다. 결과물이 1개이다.
        if (true === $isSingle) {
            $class->keyName = $rightKeyName;
        }

        $row = $attribute;

        if ($parentTableName) {
            $row = $attribute[$parentTableName];
        }

        if ($row instanceof self) {
            $row = $row->toArray();
        }

        if (false === \array_key_exists($leftKeyName, $row)) {
            throw new Exception($class->tableName . ': Undefined left key "' . $leftKeyName . '"');
        }

        $args = [$row[$leftKeyName]];

        foreach ($class->and as $key => $value) {
            $functionName .= 'And' . \Limepie\camelize($key);
            $args[] = $value;
        }

        $functionName .= \Limepie\camelize($rightKeyName);
        $data = \call_user_func_array([$class($connect), $functionName], $args);
        // \pr($class->tableName, $functionName, $args, $isSingle);

        if ($data instanceof Model) {
            $data->deleteLock = $class->deleteLock;
            // $data->parentNode = $class->parentNode;
        }

        if ($class->newTableName) {
            $moduleName = $class->newTableName;
        } else {
            if (true === $isSingle) {
                $append = '_model';
            } else {
                $append = '_models';
            }
            $moduleName = $class->tableName . $append;
        }

        // \pr($class::class, $data);
        // 부모 테이블이 있는 경우
        if ($parentTableName) {
            // parentNode가 있고 single인 경우 ,single일때만 적용된다. 싱글이 아니면 seq를 비교할수 없다.
            if ($class->parentNode && $isSingle) {
                foreach ($data ?? [] as $key => $value) {
                    if ('seq' !== $key && \Limepie\has_value($value)) {
                        $attribute[$parentTableName][$key] = $value;
                    }
                }
            }
            // 그 외의 경우
            else {
                $attribute[$parentTableName][$moduleName] = $data;
            }
        }
        // 부모 테이블이 없는 경우
        else {
            // parentNode가 있고 single인 경우,single일때만 적용된다. 싱글이 아니면 seq를 비교할수 없다.
            if ($class->parentNode && $isSingle) {
                foreach ($data ?? [] as $key => $value) {
                    if ('seq' !== $key && \Limepie\has_value($value)) {
                        $attribute[$key] = $value;
                    }
                }
            }
            // 그 외의 경우
            else {
                $attribute[$moduleName] = $data;
            }
        }

        return $attribute;
    }

    public function getRelationsData($class, $attributes, $functionName, $parentTableName = '', $isSingle = true)
    {
        $seqs = [];
        $data = [];

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

        // if ($isSingle) {
        //     $class->keyName = $rightKeyName;
        // } else {
        //     $remapKey       = $class->keyName;
        //     $class->keyName = $leftKeyName;

        //     // \pr($isSingle, $class->tableName, $remapKey, $class->keyName);
        // }

        // 어플단에서 keyName과 매핑하기 위해 임시저장하고
        // 아래는 model에 findBy하기 위해 원래의 seq로 조정해줌
        $remapKey = $class->keyName;

        if ($isSingle) {
            // single일 경우는 right로 바꿔야 한다. 중복이 없어 left와 바로 매칭할수 있다.
            $class->keyName = $rightKeyName;
        } else {
            // key를 임의로 바꾸면 데이터가 전부 오지 않을수 있다.
            // many to many이 경우 many의 key가 unique하지 않기때문이다.
            // 무조건 seq를 기반으로 가져와야 한다.
            // 다 가져오고 어플단에서 keyName과 매칭해야함
            $class->keyName = $class->primaryKeyName;
        }

        if ($class->pdo) {
            $connect = $class->pdo;
        } else {
            $connect = $this->getConnect();
        }

        foreach ($attributes as $attribute) {
            if ($parentTableName) {
                $row = $attribute[$parentTableName];
            } else {
                $row = $attribute;
            }

            if ($row instanceof self) {
                $row = $row->toArray();
            }

            if (true === \array_key_exists($leftKeyName, $row)) {
                if (null !== $row[$leftKeyName]) {
                    $seqs[] = $row[$leftKeyName];
                }
            } else {
                // \prx($parentTableName, $leftKeyName, $attribute, $row);
                if ($parentTableName) {
                    throw new Exception($this->tableName . ' table > ' . $parentTableName . ' table ' . $leftKeyName . ' column not found #2');
                }

                throw new Exception($this->tableName . ' table ' . $leftKeyName . ' column not found #2');
            }
        }

        if ($seqs) {
            // seq기반으로 가져온다.
            $seqs = \array_unique($seqs);
            $functionName .= \Limepie\camelize($rightKeyName);
            $args = [$seqs];

            foreach ($class->and as $key => $value) {
                $functionName .= 'And' . \Limepie\camelize($key);
                $args[] = $value;
            }

            // $class->keyName = $orgKey;

            $data = \call_user_func_array([$class($connect), $functionName], $args);

            // \pr($class->tableName, $orgKey, $class->keyName, $remapKey, $leftKeyName, $rightKeyName, $functionName, $args, $data);
        }
        // \pr($data);

        if ($class->newTableName) {
            $moduleName = $class->newTableName;
        } else {
            if (true === $isSingle) {
                $append = '_model';
            } else {
                $append = '_models';
            }
            $moduleName = $class->tableName . $append;
        }

        // \pr($class::class, $moduleName, $isSingle);

        if ($isSingle) {
            // $group = [];

            // single일 경우는  중복이 없어 left와 바로 매칭할수 있다.
            // 매핑과정 삭제
            // foreach ($data ?? [] as $key => $row) {
            //     $group[$row[$rightKeyName]][$key] = $row;
            // }

            foreach ($attributes as $attribute) {
                if ($parentTableName) {
                    $leftKeyValue = $attribute[$parentTableName][$leftKeyName] ?? false;
                } else {
                    $leftKeyValue = $attribute[$leftKeyName] ?? false;
                }
                // \pr($attribute, $leftKeyName, $rightKeyName, $leftKeyValue, $data, $group);

                if ($leftKeyValue && true === isset($data[$leftKeyValue]) && $data[$leftKeyValue]) {
                    $data[$leftKeyValue]->deleteLock = $class->deleteLock;
                    // $data[$attr]->parentNode = $class->parentNode;

                    // match 한다음 match키를 삭제하는지?
                    // match후 parentNode로 갈경우 같은 키 때문에 문제될수 있는것 방지
                    if (false === $class->matchKeyRemove && 'seq' !== $rightKeyName) {
                        // prx($leftKeyName, $rightKeyName, $data);
                        unset($data[$leftKeyValue][$rightKeyName]);
                    }
                }

                if ($parentTableName) {
                    // $attribute[$parentTableName]->offsetSet($moduleName, $data[$leftKeyValue] ?? null);

                    if ($class->parentNode) {
                        // parentNode가 true일 경우, 부모에게 자식을 붙인다.
                        foreach ($data[$leftKeyValue] ?? [] as $key => $value) {
                            if ('seq' !== $key && \Limepie\has_value($value)) {
                                $attribute[$parentTableName][$key] = $value;
                            }
                        }
                        // $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    } else {
                        $attribute[$parentTableName]->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    }
                } else {
                    if ($class->parentNode) {
                        // parentNode가 true일 경우, 부모에게 자식을 붙인다.
                        foreach ($data[$leftKeyValue] ?? [] as $key => $value) {
                            if ('seq' !== $key && \Limepie\has_value($value)) {
                                $attribute[$key] = $value;
                            }
                        }
                        // $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    } else {
                        $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    }
                }
            }
        } else {
            try {
                // right에 primary key name으로 relation[s]으로 매칭되는 배열을 만든다.
                $rightKeyMapValues = [];
                // \prx($rightKeyName, $data, $data->originAttributes);

                foreach ($data ?? [] as $key => $row) {
                    $keyName                           = $row->originAttributes[$rightKeyName];
                    $rightKeyMapValues[$keyName][$key] = $row;
                }
            } catch (\Throwable $e) {
                // \pr($this->tableName, $rightKeyName);
                // \pr($data);

                throw $e;
            }
            // \pr($rightKeyName, $rightKeyMapValues);

            // if ('service_menu_item_match_access_group' == $class->tableName) {
            //     $newArray = [];
            //     \pr($class->keyName, $leftKeyName, $rightKeyName, $functionName, $seqs, $data, $rightKeyMapValues);

            //     $newAttributes = [];

            //     foreach ($attributes as $attributeKey => $attributValue) {
            //         $newAttributes[$attributeKey] = $attributValue;

            //         if ($parentTableName) {
            //             $leftKeyValue = $attributValue[$parentTableName][$leftKeyName] ?? '';
            //         } else {
            //             $leftKeyValue = $attributValue[$leftKeyName] ?? '';
            //         }

            //         \pr($attributeKey, $attributValue[$leftKeyName], $leftKeyValue);

            //         if (isset($rightKeyMapValues[$attributeKey])) {
            //             // keyName 리매핑
            //             $newKeyAttribute = [];

            //             foreach ($rightKeyMapValues[$attributeKey] as $newKeyAttributeinside) {
            //                 $newKeyAttribute[$newKeyAttributeinside[$remapKey]] = $newKeyAttributeinside;
            //             }
            //             $newAttributes[$attributeKey]['service_menu_item_match_access_group'] = $newKeyAttribute;
            //         }
            //     }

            //     \pr($newAttributes);
            // }

            foreach ($attributes as $attribute) {
                if ($parentTableName) {
                    $leftKeyValue = $attribute[$parentTableName][$leftKeyName] ?? '';
                } else {
                    $leftKeyValue = $attribute[$leftKeyName] ?? '';
                }

                // left와 매칭되는 값이 있을때
                if ($leftKeyValue && true === isset($rightKeyMapValues[$leftKeyValue])) {
                    if ($class->keyName === $remapKey) { // 같으므로 remap할 필요가 없다.
                        $rightKeyMapValueByLeftKey = $rightKeyMapValues[$leftKeyValue];
                    } else {
                        // \pr($class->keyName, $remapKey, $rightKeyMapValues);
                        $rightKeyMapValueByLeftKey = [];

                        foreach ($rightKeyMapValues[$leftKeyValue] as $key => $value) {
                            // parent node에서 온것을 허용하기 위해
                            // 어차피 value에 없으면 오류가 나기때문에
                            // if (false === \in_array($remapKey, $value->allColumns, true)) {
                            //     throw new Exception($remapKey . ' column not found #3');
                            // }

                            // if (false === \array_key_exists($remapKey, $value->attributes)) {
                            //     throw new Exception($remapKey . ' column is null, not match');
                            // }

                            if ($remapKey instanceof \Closure) {
                                $innerKeyName = ($remapKey)($value->originAttributes);
                            } else {
                                $innerKeyName = $value->originAttributes[$remapKey];
                            }
                            $rightKeyMapValueByLeftKey[$innerKeyName] = $value;
                        }
                    }

                    $instance             = new $class($connect, $rightKeyMapValueByLeftKey);
                    $instance->deleteLock = $class->deleteLock;
                    // $instance->parentNode = $class->parentNode;

                    // //\pr($class::class, $rightKeyMapValueByLeftKey);

                    if ($parentTableName) {
                        // $attribute[$parentTableName]->offsetSet($moduleName, $instance);

                        if ($class->parentNode) { // parent로 옮길때는 seq까지 옮기면 덮어 쓴다.
                            foreach ($rightKeyMapValueByLeftKey ?? [] as $key => $value) {
                                if ('seq' !== $key && \Limepie\has_value($value)) {
                                    $attribute[$parentTableName][$key] = $value;
                                }
                            }
                        } else {
                            $attribute[$parentTableName]->offsetSet($moduleName, $instance);
                        }
                    } else {
                        if ($class->parentNode) { // parent로 옮길때는 seq까지 옮기면 덮어 쓴다.
                            foreach ($rightKeyMapValueByLeftKey ?? [] as $key => $value) {
                                if ('seq' !== $key && \Limepie\has_value($value)) {
                                    $attribute[$key] = $value;
                                }
                            }
                        } else {
                            $attribute->offsetSet($moduleName, $instance);
                        }
                    }
                } else {
                    if ($parentTableName) {
                        $attribute[$parentTableName]->offsetSet($moduleName, null);
                    } else {
                        $attribute->offsetSet($moduleName, null);
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
                $attributes = $this->getRelationsData(
                    $class,
                    $attributes,
                    functionName: 'getsBy',
                    isSingle: true
                );
            }
        }

        // if ($this->oneToOnes ?? false) {
        //     \pr($this->oneToOnes);
        // }

        // if ($this->oneToManies ?? false) {
        //     \pr($this->oneToManies);
        // }

        if ($this->oneToOnes ?? false) {
            foreach ($this->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    $attributes = $this->getRelationsData(
                        $class,
                        $attributes,
                        functionName: 'getsBy',
                        parentTableName: $parentTableName,
                        isSingle: true
                    );
                }
            }
        }

        if ($this->oneToMany) {
            foreach ($this->oneToMany as $class) {
                $attributes = $this->getRelationsData(
                    $class,
                    $attributes,
                    functionName: 'getsBy',
                    isSingle: false
                );
            }
        }

        if ($this->oneToManies ?? []) {
            foreach ($this->oneToManies as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    $attributes = $this->getRelationsData(
                        $class,
                        $attributes,
                        functionName: 'getsBy',
                        parentTableName: $parentTableName,
                        isSingle: false
                    );
                }
            }
        }

        return $attributes;
    }
}
