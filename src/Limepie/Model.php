<?php

declare(strict_types=1);

namespace Limepie;

class Model extends ModelBase
{
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
                    if ('seq' !== $key) {
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
                    if ('seq' !== $key) {
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
                            if ('seq' !== $key) {
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
                            if ('seq' !== $key) {
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

                            if ($class->secondKeyName) {
                                $rightKeyMapValueByLeftKey[$value[$remapKey]][$value[$class->secondKeyName]] = $value;
                            } else {
                                $rightKeyMapValueByLeftKey[$value[$remapKey]] = $value;
                            }
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
                                if ('seq' !== $key) {
                                    $attribute[$parentTableName][$key] = $value;
                                }
                            }
                        } else {
                            $attribute[$parentTableName]->offsetSet($moduleName, $instance);
                        }
                    } else {
                        if ($class->parentNode) { // parent로 옮길때는 seq까지 옮기면 덮어 쓴다.
                            foreach ($rightKeyMapValueByLeftKey ?? [] as $key => $value) {
                                if ('seq' !== $key) {
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

    public function replace() {}

    public function create($on_duplicate_key_update = null)
    {
        $columns = [];
        $binds   = [];
        $values  = [];

        foreach ($this->allColumns as $column) {
            if ($this->sequenceName === $column) {
            } else {
                if ('created_ts' === $column || 'updated_ts' === $column) {
                    if ($this->attributes[$column] ?? false) {
                        $columns[]            = '`' . $column . '`';
                        $values[]             = ':' . $column;
                        $binds[':' . $column] = $this->attributes[$column];
                    }
                } elseif ('ip' === $column) {
                    $columns[]            = '`' . $column . '`';
                    $binds[':' . $column] = $this->attributes[$column] ?? \Limepie\getIp();
                    $values[]             = 'inet6_aton(:' . $column . ')';
                } elseif ('aes_serialize' === $this->dataStyles[$column]) {
                    $columns[]                           = '`' . $column . '`';
                    $binds[':' . $column]                = \serialize($this->attributes[$column] ?? null);
                    $binds[':' . $column . '_secretkey'] = Aes::$salt;
                    $values[]                            = 'AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey)';
                } elseif ('aes' === $this->dataStyles[$column]) {
                    $columns[]                           = '`' . $column . '`';
                    $binds[':' . $column]                = $this->attributes[$column] ?? null;
                    $binds[':' . $column . '_secretkey'] = Aes::$salt;
                    $values[]                            = 'AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey)';
                } elseif ('aes_hex' === $this->dataStyles[$column]) {
                    $columns[]                           = '`' . $column . '`';
                    $binds[':' . $column]                = $this->attributes[$column] ?? null;
                    $binds[':' . $column . '_secretkey'] = Aes::$salt;
                    $values[]                            = 'HEX(AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey))';
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && true  === isset($this->attributes[$column])
                    && true  === \is_array($this->attributes[$column])
                    && false === isset($this->rawAttributes[$column])
                ) {
                    $columns[] = '`' . $column . '`';
                    $value     = $this->attributes[$column];

                    if (true === \is_null($value)) {
                        throw new Exception('empty point value');
                    }
                    $binds[':' . $column . '1'] = $value[0];
                    $binds[':' . $column . '2'] = $value[1];

                    $values[] = 'point(:' . $column . '1, :' . $column . '2)';
                } elseif (true === \array_key_exists($column, $this->attributes)) {
                    $value = $this->attributes[$column];

                    if (true === isset($this->dataStyles[$column])) {
                        switch ($this->dataStyles[$column]) {
                            case 'curlfile_serialize':
                                $value = CurlFile::serialize($value);

                                break;
                            case 'serialize':
                                $value = \serialize($value);

                                break;
                            case 'base64':
                                $value = \base64_encode(\serialize($value));

                                break;
                            case 'gz':
                                $value = \gzcompress(\serialize($value), 9);

                                break;
                                // case 'aes':
                                //     $value = \Limepie\Aes::pack($value);

                                //     break;
                            case 'jsons':
                                // $value = \json_encode($value);

                                break;
                            case 'json':
                                if (false === \is_null($value)) {
                                    $value = \json_encode($value);
                                }

                                break;
                            case 'yml':
                            case 'yaml':
                                $value = \yaml_emit($value);

                                break;
                        }
                    }

                    // if (true === isset($this->plusAttributes[$column])) {
                    //     $columns[] = '`' . $column . '`';
                    //     $values[]  = '`' . $column . '` + ' . $this->plusAttributes[$column];
                    // } elseif (true === isset($this->minusAttributes[$column])) {
                    //     $columns[] = '`' . $column . '`';
                    //     $values[]  = '`' . $column . '` - ' . $this->minusAttributes[$column];
                    // } else

                    if (true === isset($this->rawAttributes[$column])) {
                        $columns[] = "`{$this->tableName}`." . '`' . $column . '`';
                        $values[]  = \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $binds += $value;
                        } else {
                            throw new Exception($column . ' raw bind error');
                        }
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

        if ($on_duplicate_key_update) {
            $sql .= ' ' . $on_duplicate_key_update;
        }
        $primaryKey = '';

        if (static::$debug) {
            $this->print($sql, $binds);
            Timer::start();
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
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        if ($primaryKey) {
            $this->primaryKeyValue = $primaryKey;
            $this->plusAttributes  = [];

            return $this;
        }

        return false;
    }

    public function update($checkUpdatedTs = false)
    {
        if (false === isset($this->attributes[$this->primaryKeyName])) {
            $debug = \debug_backtrace()[0];

            throw (new Exception('not found ' . $this->primaryKeyName))
                ->setDebugMessage('models update?', $debug['file'], $debug['line'])
            ;
        }

        $this->changeBinds   = [];
        $this->changeColumns = [];
        $this->sameColumns   = [];

        foreach ($this->allColumns as $column) {
            // db에서 가져온것과 비교해서 바뀌지 않으면 업데이트 하지 않음

            $attr     = $this->attributes[$column]       ?? null;
            $origAttr = $this->originAttributes[$column] ?? null;

            // attr과 originAttr가 같고 raw, plus,  minus가 아니면 continue,
            // raw, plus, minus가 있으면 값은 변동되지 않아도 업데이트 함
            if ($attr    === $origAttr
                && false === isset($this->plusAttributes[$column])
                && false === isset($this->minusAttributes[$column])
                && false === isset($this->rawAttributes[$column])
            ) {
                // $this->sameColumns[$column] = $origAttr;

                continue;
            }

            if (true === isset($this->dataStyles[$column])
                && 'jsons' == $this->dataStyles[$column]) {
                if (true === isset($this->originAttributes[$column]) && $this->originAttributes[$column]) {
                    if ($this->originAttributes[$column] instanceof ArrayObject) {
                        $target = $this->originAttributes[$column]->attributes;
                    } else {
                        $target = $this->originAttributes[$column];
                    }

                    if (\is_string($this->attributes[$column])) {
                        if (\json_decode($this->attributes[$column], true) == $target) {
                            continue;
                        }
                    } elseif ($this->attributes[$column] == $target) {
                        continue;
                    }
                }
            }

            if ($this->sequenceName === $column) {
            } else {
                if ('created_ts' === $column || 'updated_ts' === $column) {
                    if ($this->attributes[$column] ?? false) {
                        $this->changeColumns[]            = "`{$this->tableName}`." . '`' . $column . '` = :' . $column;
                        $this->changeBinds[':' . $column] = $this->attributes[$column];
                    }
                } elseif ('ip' === $column) {
                    $this->changeColumns[]            = "`{$this->tableName}`." . '`' . $column . '` = inet6_aton(:' . $column . ')';
                    $this->changeBinds[':' . $column] = $this->attributes[$column] ?? \Limepie\getIp();
                } elseif ('aes_serialize' === $this->dataStyles[$column]) {
                    $this->changeColumns[]                           = "`{$this->tableName}`." . '`' . $column . '` = AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey)';
                    $this->changeBinds[':' . $column]                = \serialize($this->attributes[$column] ?? null);
                    $this->changeBinds[':' . $column . '_secretkey'] = Aes::$salt;
                } elseif ('aes' === $this->dataStyles[$column]) {
                    $this->changeColumns[]                           = "`{$this->tableName}`." . '`' . $column . '` = AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey)';
                    $this->changeBinds[':' . $column]                = $this->attributes[$column] ?? null;
                    $this->changeBinds[':' . $column . '_secretkey'] = Aes::$salt;
                } elseif ('aes_hex' === $this->dataStyles[$column]) {
                    $this->changeColumns[]                           = "`{$this->tableName}`." . '`' . $column . '` = HEX(AES_ENCRYPT(:' . $column . ', :' . $column . '_secretkey))';
                    $this->changeBinds[':' . $column]                = $this->attributes[$column] ?? null;
                    $this->changeBinds[':' . $column . '_secretkey'] = Aes::$salt;
                } elseif (
                    true === isset($this->dataStyles[$column])
                    && 'point' == $this->dataStyles[$column]
                    && false === isset($this->rawAttributes[$column])
                ) {
                    if (true === \is_array($this->attributes[$column])) {
                        $value = $this->attributes[$column];

                        if (true === \is_null($value)) {
                            throw new Exception('empty point value');
                        }

                        $this->changeColumns[] = "`{$this->tableName}`." . '`' . $column . '` = point(:' . $column . '1, :' . $column . '2)';

                        $this->changeBinds[':' . $column . '1'] = $value[0];
                        $this->changeBinds[':' . $column . '2'] = $value[1];
                    }
                } elseif (true === \array_key_exists($column, $this->attributes)) {
                    $value = $this->attributes[$column];

                    if (true === isset($this->dataStyles[$column])) {
                        switch ($this->dataStyles[$column]) {
                            case 'curlfile_serialize':
                                $value = CurlFile::serialize($value);

                                break;
                            case 'serialize':
                                $value = \serialize($value);

                                break;
                            case 'base64':
                                $value = \base64_encode(\serialize($value));

                                break;
                            case 'gz':
                                $value = \gzcompress(\serialize($value), 9);

                                break;
                                // case 'aes':
                                //     $value = \Limepie\Aes::pack($value);

                                //     break;
                            case 'jsons':
                                // $value = \json_encode($value);

                                break;
                            case 'json':
                                if (false === \is_null($value)) {
                                    $value = \json_encode($value);
                                }

                                break;
                            case 'yml':
                            case 'yaml':
                                $value = \yaml_emit($value);

                                break;
                        }
                    }

                    if (true === isset($this->plusAttributes[$column])) {
                        $this->changeColumns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . "`{$this->tableName}`." . '`' . $column . '` + ' . $this->plusAttributes[$column];
                    } elseif (true === isset($this->minusAttributes[$column])) {
                        $name = "`{$this->tableName}`." . '`' . $column . '`';

                        $this->changeColumns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . "IF({$name} > 0, {$name} - " . $this->minusAttributes[$column] . ', 0)';
                    } elseif (true === isset($this->rawAttributes[$column])) {
                        $this->changeColumns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $this->changeBinds += $value;
                        } else {
                            throw new Exception($column . ' raw bind error');
                        }
                    } else {
                        $this->changeColumns[]            = "`{$this->tableName}`." . '`' . $column . '` = :' . $column;
                        $this->changeBinds[':' . $column] = $value;
                    }
                }
            }
        }

        if ($this->changeColumns) {
            $column = \implode(', ', $this->changeColumns);
            $where  = $this->primaryKeyName;
            $sql    = <<<SQL
                UPDATE
                    `{$this->tableName}`
                SET
                    {$column}
                WHERE
                    `{$where}` = :{$where}
            SQL;

            $this->changeBinds[':' . $this->primaryKeyName] = $this->attributes[$this->primaryKeyName];

            if (true === $checkUpdatedTs) {
                $sql .= ' AND updated_ts = :check_updated_ts';
                $this->changeBinds[':check_updated_ts'] = $this->attributes['updated_ts'];
            }

            if (static::$debug) {
                $this->print($sql, $this->changeBinds);
                Timer::start();
            }

            if ($this->getConnect()->set($sql, $this->changeBinds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
                }
                $this->plusAttributes = [];

                return $this;
            }

            return false;
        }

        return $this;
    }

    public function doDelete() : bool|self
    {
        if (true == $this->deleteLock) {
            return true;
        }
        // \prx($this->tableName, $this->attributes);

        if (true === isset($this->attributes[$this->primaryKeyName])) {
            $sql = <<<SQL
                DELETE
                FROM
                    `{$this->tableName}`
                WHERE
                    `{$this->primaryKeyName}` = :{$this->primaryKeyName}
            SQL;

            $binds = [
                $this->primaryKeyName => $this->originAttributes[$this->primaryKeyName],
            ];

            if (static::$debug) {
                $this->print($sql, $binds);
                Timer::start();
            }

            if ($this->getConnect()->set($sql, $binds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
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
                $object->primaryKeyName => $object->originAttributes[$object->primaryKeyName],
            ];

            if (static::$debug) {
                $this->print($sql, $binds);
                Timer::start();
            }

            if ($this->getConnect()->set($sql, $binds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
                }
                $object->primaryKeyValue = '';
                $object->attributes      = [];
                // $object->originAttributes = [];
                unset($ojbect);
                $result = true;
            }
        }

        if ($result) {
            $this->primaryKeyValue = '';
            $this->attributes      = [];
            // 삭제해도 origin은 보관
            // $this->originAttributes = [];

            return $this;
        }

        return false;
    }

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
                        $columns[] = "AES_DECRYPT(UNHEX(`{$this->tableAliasName}`." . '`' . $alias . '`), :' . $prefix . $alias . '_secretkey' . $rand . ') AS `' . $prefix . $alias . '`';

                        if (false === $isCount) {
                            $this->binds[':' . $prefix . $alias . '_secretkey' . $rand . ''] = Aes::$salt;
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
                                $columns[] = "AES_DECRYPT(UNHEX(`{$this->tableAliasName}`." . '`' . $alias->columnName . '`), :' . $prefix . $alias->aliasName . '_secretkey' . $rand . ') AS `' . $prefix . $alias->aliasName . '`';

                                if (false === $isCount) {
                                    $this->binds[':' . $prefix . $alias->aliasName . '_secretkey' . $rand . ''] = Aes::$salt;
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

        return \implode(PHP_EOL . '        , ', $columns);
    }

    protected function buildCount(string $name, array $arguments, int $offset, $isGroup = false)
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);
        $selectColumns       = $this->getSelectColumns(isCount: true);

        $condition .= $this->condition;
        $binds += $this->binds;

        $orderBy = $this->getOrderBy();
        $limit   = $this->getLimit();
        $join    = '';

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin(isCount: true);
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

            // $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }

        if (true === $isGroup) {
            if ($this->groupKey) { // 이 케이스는 아래 select시 문제될듯, 전수조사 필요.
                $group = $this->groupBy . ' AS ' . $this->groupKey;
            } else {
                $group = $this->groupBy;
            }
            $sql = <<<SQL
                SELECT
                    {$group},
                    COUNT(*) as row_count
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$join}
                {$condition}
                group by {$this->groupBy}
            SQL;
        } else {
            if ($this->groupBy) {
                $sql = <<<SQL
                SELECT
                    COUNT(distinct({$this->groupBy}))
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$join}
                {$condition}
            SQL;
            } else {
                $sql = <<<SQL
                SELECT
                    COUNT(*)
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$join}
                {$condition}
            SQL;
            }
        }

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if (static::$debug) {
            $this->print(null, null);
            Timer::start();
        }

        if ($this->getConnect() instanceof \PDO) {
            if (true === $isGroup) {
                $data = $this->getConnect()->gets($sql, $binds, false);

                $attributes = [];
                $class      = \get_called_class();

                // group by 한 키네임, 보통 특정 keyName을 기반으로 한다.
                foreach ($data as $index => &$row) {
                    if ($this->keyName) {
                        if ($this->keyName instanceof \Closure) {
                            $keyName = ($this->keyName)($row);
                        } else {
                            $keyName = $row[$this->keyName];
                        }

                        $attributes[$keyName] = new $class($this->getConnect(), $row);
                    } else {
                        // primary가 없다.
                        $attributes[] = new $class($this->getConnect(), $row);
                    }
                }
            } else {
                $attributes = $this->getConnect()->get1($sql, $binds, false);
            }

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }

            return $attributes;
        }

        throw new Exception('lost connection');
    }

    protected function buildGetSum(string $name, array $arguments, int $offset) : float|int
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $selectColumns = $this->getSelectColumns(isCount: true);
        $condition .= $this->condition;
        $binds += $this->binds;

        $orderBy = $this->getOrderBy();
        $limit   = $this->getLimit();
        $join    = '';

        $sumColumn = $this->sumColumn;

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin(isCount: true);

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

            // $keyName = '';
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
            Timer::start();
        }

        if ($this->getConnect() instanceof \PDO) {
            $data = $this->getConnect()->get1($sql, $binds, false);

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }

            return \Limepie\decimal($data);
        }

        throw new Exception('lost connection');
    }

    protected function buildGetAvg(string $name, array $arguments, int $offset) : float|int
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $selectColumns = $this->getSelectColumns(isCount: true);
        $condition .= $this->condition;
        $binds += $this->binds;

        $orderBy = $this->getOrderBy();
        $limit   = $this->getLimit();
        $join    = '';

        $avgColumn = $this->avgColumn;

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin(isCount: true);

            if ($joinInfomation['avgColumn']) {
                $avgColumn = $joinInfomation['avgColumn'];
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

            // $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }
        $sql = <<<SQL
            SELECT
                AVG({$avgColumn})
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
            Timer::start();
        }

        if ($this->getConnect() instanceof \PDO) {
            $data = $this->getConnect()->get1($sql, $binds, false);

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }

            return \Limepie\decimal($data);
        }

        throw new Exception('lost connection');
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

    public function get1(null|array|string $sql = null, array $binds = []) : ?self
    {
        throw new Exception('not support get1');
    }

    protected function buildGroupBy(string $groupByString, array $arguments) : self
    {
        $part = \explode('And', \substr($groupByString, 7));

        $groupBy = [];

        foreach ($part as $name) {
            if (1 === \preg_match('#(?P<column>.*)(?P<how>Asc|Desc)$#U', $name, $m)) {
                if (true === isset($arguments[0])) {
                    $groupBy[] = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . \strtoupper($m['how']);
                } else {
                    $groupBy[] = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ' . \strtoupper($m['how']);
                }
            } elseif (1 === \preg_match('#(?P<column>.*)#', $name, $m)) {
                if (true === isset($arguments[0])) {
                    $groupBy[] = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . '';
                } else {
                    $groupBy[] = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '`';
                }
            } else {
                throw new Exception('"' . $name . '" syntax error', 1999);
            }
        }
        $this->groupBy = \implode(', ', $groupBy);

        return $this;
    }

    protected function buildOrderBy(string $orderByString, array $arguments) : self
    {
        $part = \explode('And', \substr($orderByString, 7));

        $orderBy = [];

        foreach ($part as $name) {
            if (1 === \preg_match('#(?P<column>.*)(?P<how>Asc|Desc)$#U', $name, $m)) {
                if (true === isset($arguments[0])) {
                    $orderBy[] = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . \strtoupper($m['how']);
                } else {
                    $orderBy[] = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ' . \strtoupper($m['how']);
                }
            } elseif (1 === \preg_match('#(?P<column>.*)#', $name, $m)) {
                if (true === isset($arguments[0])) {
                    $orderBy[] = \sprintf($arguments[0], "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ') . 'ASC';
                } else {
                    $orderBy[] = "`{$this->tableAliasName}`." . '`' . \Limepie\decamelize($m['column']) . '` ASC';
                }
            } else {
                throw new Exception('"' . $name . '" syntax error', 1999);
            }
        }
        $this->orderBy = \implode(', ', $orderBy);

        return $this;
    }

    private function isValidSeqArgument(string $operator, mixed $argument) : bool
    {
        if ('seq' !== \strtolower(\substr($operator, -3))) {
            return true; // Not a Seq operator, so it's valid
        }

        if (\is_array($argument)) {
            // Check if all elements in the array are numeric
            return \array_reduce($argument, function (bool $allNumeric, mixed $element) {
                return $allNumeric && \is_numeric($element);
            }, true);
        }

        return \is_numeric($argument) || null === $argument;
    }

    protected function buildAnd(string $name, array $arguments, int $offset = 3) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true)) {
            $this->condition .= ' AND ' . $operator;
        } elseif (false !== \strpos($operator, ' ')) { // pure sql
            $this->condition .= ' AND ' . $operator;

            if (isset($arguments[0])) {
                $this->binds += $arguments[0];
            }
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

    protected function buildOr(string $name, array $arguments, int $offset = 2) : self
    {
        $operator = \substr($name, $offset);

        if (true === \in_array($operator, [')', '('], true)) {
            $this->condition .= ' OR ' . $operator;
        } elseif (false !== \strpos($operator, ' ')) { // pure sql
            $this->condition .= ' OR ' . $operator;

            if (isset($arguments[0])) {
                // \pr($name, $arguments);
                // \var_dump($arguments);
                $this->binds += $arguments[0];
            }
        } else {
            [$conds, $binds] = $this->getConditions($name, $arguments, $offset);

            if ($conds) {
                $this->condition .= ' OR ' . PHP_EOL . '        ' . \trim(\implode(PHP_EOL . '        ', $conds));
            }

            if ($binds) {
                $this->binds += $binds;
            }
        }

        return $this;
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

                // orperator의 마지막이 seq로 끝날경우, 값이 숫자나 null이 아니라면 처리하지 않음
                if (!$this->isValidSeqArgument($key, $arguments[$index] ?? null)) {
                    // \prx($this->tableName, $this->condition, $name, $arguments, $index, $key, $arguments[$index] ?? null);

                    continue;
                }

                if (false === \array_key_exists($index, $arguments)) {
                    // \pr($name, $arguments, $offset, $open, $key, $close, $operator, $splitKey);
                    // string 자체를 query로 사용하므로 bind변수가 없다.
                    $queryString = $key;

                    // throw new \Limepie\Exception($key . ': numbers of columns of arguments do not match');
                } elseif (0 === \strpos($key, 'fulltext_boolean_')) {
                    $fixedKey = \substr($key, 17);

                    $queryString = "MATCH(`{$this->tableAliasName}`." . '`' . $fixedKey . '` ) AGAINST (CONCAT("+", :' . $bindKeyname . ', "*") IN BOOLEAN MODE)';

                    $binds[':' . $bindKeyname] = $arguments[$index];
                } elseif (0 === \strpos($key, 'fulltext_')) {
                    $fixedKey = \substr($key, 9);

                    $queryString = "MATCH(`{$this->tableAliasName}`." . '`' . $fixedKey . '` ) AGAINST (:' . $bindKeyname . ' IN NATURAL LANGUAGE MODE)';

                    $binds[':' . $bindKeyname] = $arguments[$index];
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

    public function get(null|array|string $sql = null, array $binds = []) : ?self
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
                }

                if ($this->binds) {
                    $binds = $this->binds;
                }
            }

            if (true === isset($args['binds'])) {
                $binds = $args['binds'];
            }

            if (!$condition && $this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }

            // $selectColumns = $this->getSelectColumns();

            if ($this->joinModels) {
                $joinInfomation = $this->getJoin();
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

                // $keyName = '';
            }

            if ($condition) {
                $condition = ' WHERE ' . $condition;
            }

            if ($this->rawColumnString) {
                $selectColumns .= ',' . $this->rawColumnString;
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
            Timer::start();
        }

        return $this->executeGet($sql, $binds);
    }

    protected function buildGetBy(string $name, array $arguments, int $offset)
    {
        $this->attributes = [];

        $condition           = '';
        $binds               = [];
        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $selectColumns = $this->getSelectColumns();
        $condition .= $this->condition;
        $binds += $this->binds;

        $orderBy = $this->getOrderBy();
        $limit   = $this->getLimit();
        $join    = '';

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin();
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

            // $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }

        if ($this->rawColumnString) {
            $selectColumns .= ',' . $this->rawColumnString;
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

        return $this->executeGet($sql, $binds);
    }

    public function executeGet(string $sql, array $binds = [])
    {
        if ($this->getConnect() instanceof \PDO) {
            if (static::$debug) {
                $this->print(null, null);
                Timer::start();
            }

            $attributes = $this->getConnect()->get($sql, $binds, false);

            foreach ($this->callbackColumns as $callbackColumn) {
                $attributes[$callbackColumn['alias']] = $callbackColumn['callback']($attributes[$callbackColumn['column']]);
            }

            if (static::$debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }
        } else {
            throw new Exception('lost connection');
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
                        $tmp[\Limepie\str_replace_first($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($attributes[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                if ($joinModel->parentNode) {
                    // parentNode가 true일 경우, 부모에게 자식을 붙인다.
                    foreach ($tmp ?? [] as $key => $value) {
                        if ('seq' !== $key) {
                            $attributes[$key] = $value;
                        }
                    }
                    // $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                } else {
                    $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                    if ($attributes[$parentTableName] instanceof self) {
                        $attributes[$parentTableName]->deleteLock = $joinModel->deleteLock;
                        // $attributes[$parentTableName]->parentNode = $joinModel->parentNode;
                    }
                }

                // $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            $this->originAttributes = $this->attributes = $this->getRelation($attributes);
            $this->primaryKeyValue  = $this->originAttributes[$this->primaryKeyName] ?? null;

            if ($this->valueName instanceof \Closure) {
                return $this->attributes = ($this->valueName)($this->attributes);
            }

            return $this;
        }

        return $this->empty();
    }

    protected function buildGetsBy(string $name, array $arguments, int $offset) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';

        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $selectColumns = $this->getSelectColumns();
        $condition .= $this->condition;
        $binds += $this->binds;

        $groupBy    = $this->getGroupBy();
        $orderBy    = $this->getOrderBy();
        $limit      = $this->getLimit();
        $join       = '';
        $forceIndex = \implode(', ', $this->forceIndexes);

        if ($this->joinModels) {
            $joinInfomation = $this->getJoin();
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

            if ($joinInfomation['orderBy']) {
                if ($orderBy) {
                    $orderBy .= ', ' . $joinInfomation['orderBy'];
                } else {
                    $orderBy = $this->getOrderBy($joinInfomation['orderBy']);
                }
            }
            // $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }

        if ($this->rawColumnString) {
            $selectColumns .= ',' . $this->rawColumnString;
        }
        $sql = <<<SQL
            SELECT
                {$selectColumns}
            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
            {$forceIndex}
            {$join}
            {$condition}
            {$groupBy}
            {$orderBy}
            {$limit}
        SQL;

        $this->condition = $condition;
        $this->query     = $sql;
        $this->binds     = $binds;

        if (static::$debug) {
            $this->print(null, null);
            Timer::start();
        }

        return $this->executeGets($sql, $binds);
    }

    public function gets(null|array|string $sql = null, array $binds = []) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';

        if (false === \is_string($sql)) {
            $args          = $sql;
            $orderBy       = $this->getOrderBy($args['order'] ?? null);
            $limit         = $this->getLimit();
            $condition     = '';
            $binds         = [];
            $join          = '';
            $selectColumns = $this->getSelectColumns(isCount: false);

            if (true === isset($args['condition'])) {
                $condition = ' ' . $args['condition'];
            } else {
                if ($this->condition) {
                    $condition = '  ' . $this->condition;
                }

                if ($this->binds) {
                    $binds = $this->binds;
                }
            }

            if (true === isset($args['binds'])) {
                $binds = $args['binds'];
            }

            if (!$condition && $this->condition) {
                $condition = '' . $this->condition;
                $binds     = $this->binds;
            }

            if ($this->joinModels) {
                $joinInfomation = $this->getJoin();
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

                if ($joinInfomation['orderBy']) {
                    if ($orderBy) {
                        $orderBy .= ', ' . $joinInfomation['orderBy'];
                    } else {
                        $orderBy = $this->getOrderBy($joinInfomation['orderBy']);
                    }
                }

                // 조인시 왜 keyname을 무력화 했던것인가?
                // $keyName = '';
            }

            if ($condition) {
                $condition = ' WHERE ' . $condition;
            }
            $forceIndex = \implode(', ', $this->forceIndexes);

            $groupBy = $this->getGroupBy();

            if ($this->rawColumnString) {
                $selectColumns .= ',' . $this->rawColumnString;
            }

            $sql = <<<SQL
                SELECT
                    {$selectColumns}
                FROM
                    `{$this->tableName}` AS `{$this->tableAliasName}`
                {$forceIndex}
                {$join}
                {$condition}
                {$groupBy}
                {$orderBy}
                {$limit}
            SQL;
            $this->condition = $condition;
        } else {
            $orderBy = $this->getOrderBy($args['order'] ?? null);
            $limit   = $this->getLimit();

            $sql .= <<<SQL
                {$orderBy}
                {$limit}
            SQL;
        }

        $this->query = $sql;
        $this->binds = $binds;

        if (static::$debug) {
            $this->print(null, null);
            Timer::start();
        }

        return $this->executeGets($sql, $binds);
    }

    public function executeGets(string $sql, array $binds = []) : ?self
    {
        $data = $this->getConnect()->gets($sql, $binds, false);

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        $class = \get_called_class();

        $attributes = [];

        foreach ($data as $index => &$row) {
            foreach ($this->callbackColumns as $callbackColumn) {
                $row[$callbackColumn['alias']] = $callbackColumn['callback']($row[$callbackColumn['column']]);
            }

            foreach ($this->joinModels as $joinModelInfomation) {
                $joinModel          = $joinModelInfomation['model'];
                $joinClassAliasName = $joinModel->tableAliasName;
                $joinClassName      = $joinModel->tableName;

                $tmp = [];

                foreach ($row as $innerFieldName => &$innerFieldValue) {
                    if (0 === \strpos($innerFieldName, $joinClassAliasName . '_')) {
                        $tmp[\Limepie\str_replace_first($joinClassAliasName . '_', '', $innerFieldName)] = $innerFieldValue;

                        unset($row[$innerFieldName]);
                    }
                }

                unset($innerFieldValue);

                if ($joinModel->newTableName) {
                    $parentTableName = $joinModel->newTableName;
                } else {
                    $parentTableName = $joinModel->tableName . '_model';
                }

                if ($joinModel->parentNode) {
                    // parentNode가 true일 경우, 부모에게 자식을 붙인다.
                    foreach ($tmp ?? [] as $key => $value) {
                        if ('seq' !== $key) {
                            $row[$key] = $value;
                        }
                    }
                    // $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                } else {
                    $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                    if ($row[$parentTableName] instanceof Model) {
                        $row[$parentTableName]->deleteLock = $joinModel->deleteLock;
                        // $row[$parentTableName]->parentNode = $joinModel->parentNode;
                    }
                }

                // $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            if ($this->keyName) {
                if ($this->keyName instanceof \Closure) {
                    $keyName = ($this->keyName)($row);
                } else {
                    if (false === \array_key_exists($this->keyName, $row)) {
                        if ($parentTableName) {
                            throw new Exception('gets ' . $this->tableName . ' "> ' . $parentTableName . ' ' . $this->keyName . '" column not found #5');
                        }

                        throw new Exception('gets ' . $this->tableName . ' "' . $this->keyName . '" column not found #5');
                    }
                    $keyName = $row[$this->keyName];
                }
            } else {
                $keyName = $row[$this->primaryKeyName];
            }

            // if ('key' == $this->keyName) {
            //     \prx($this->keyName, $keyName, \is_callable($this->keyName));
            // }
            $attributes[$keyName] = new $class($this->getConnect(), $row);
        }
        unset($row);

        if ($attributes) {
            $attributes = $this->getRelations($attributes);

            if ($this->valueName instanceof \Closure) {
                foreach ($attributes as $key => $attribute) {
                    $attributes[$key]->attributes = ($this->valueName)($attribute);
                }
            }

            $this->attributes = $attributes;
            // gets에서는 원본 속성을 저장하지 않음, 원본속성은 개별 모델에 존재함
            // $this->originAttributes = $this->attributes;

            return $this;
        }

        return $this->empty();
    }
}
