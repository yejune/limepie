<?php declare(strict_types=1);

namespace Limepie;

use Doctrine\SqlFormatter\SqlFormatter;

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

    public $originAttributes = [];

    public $rawAttributes = [];

    public $plusAttributes = [];

    public $minusAttributes = [];

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

    public $avgColumn = '';

    public $oneToOnes = [];

    public $oneToManies = [];

    public $allColumns = [];

    public static $debug = false;

    public $callbackColumns = [];

    // update시 변경된 컬럼
    public $changeColumns = [];

    // update시 변경된 변수
    public $changeBinds = [];

    // public $sameColumns = [];

    public static function newInstance(?\PDO $pdo = null, array|ArrayObject $attributes = []) : self
    {
        return new self($pdo, $attributes);
    }

    public function __construct(?\PDO $pdo = null, array|ArrayObject $attributes = [])
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }

        $this->keyName = $this->primaryKeyName;
    }

    public function __invoke(?\PDO $pdo = null, array|ArrayObject $attributes = [])
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

        if (0 === \strpos($name, 'matchAll')) {
            $this->addAllColumns();

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

    public function setAttribute(string $column, array $attribute = [])
    {
        $this->attributes[$column] = $attribute;
    }

    public function setAttributeses(array|ArrayObject $attributes = [])
    {
        $class = \get_called_class();

        if ($attributes instanceof ArrayObject) {
            $attributes = $attributes->attributes;
        }

        foreach ($attributes as $attribute) {
            $this->attributes[] = new $class($this->pdo, $attribute);
        }

        return $this;
    }

    public function setAttributes(array|ArrayObject $attributes = [])
    {
        if ($attributes instanceof ArrayObject) {
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

            return $attributes;
        }
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

    public function getRelationData($class, $attribute, $functionName, $parentTableName = '', $isSingle = true)
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

        if ($data) {
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

        if ($parentTableName) {
            $attribute[$parentTableName][$moduleName] = $data;
        } else {
            if ($class->parentNode) {
                if ($isSingle) { // single일때만 적용된다. 싱글이 아니면 seq를 비교할수 없다.
                    foreach ($data ?? [] as $key => $value) {
                        if ('seq' !== $key) {
                            $attribute[$key] = $value;
                        }
                        // $attribute[$key] = $value;
                    }
                } else {
                    $attribute[$moduleName] = $data;
                }
            } else {
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
                }

                if ($parentTableName) {
                    $attribute[$parentTableName]->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
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

                foreach ($data ?? [] as $key => $row) {
                    $rightKeyMapValues[$row[$rightKeyName]][$key] = $row;
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
                            if (false === \in_array($remapKey, $value->allColumns, true)) {
                                throw new Exception($remapKey . ' column not found #3');
                            }

                            if (false === \array_key_exists($remapKey, $value->attributes)) {
                                throw new Exception($remapKey . ' column is null, not match');
                            }

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
                        $attribute[$parentTableName]->offsetSet($moduleName, $instance);
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
        $this->changeColumns = [];

        if (false === isset($this->attributes[$this->primaryKeyName])) {
            $debug = \debug_backtrace()[0];

            throw (new Exception('not found ' . $this->primaryKeyName))->setDebugMessage('models update?', $debug['file'], $debug['line']);
        }
        $this->changeBinds = [
            ':' . $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
        ];

        foreach ($this->allColumns as $column) {
            // if (
            //     true === isset($this->originAttributes[$column])
            //     && false == isset($this->rawAttributes[$column])
            // ) {
            //     \pr($column);

            //     // null일 경우에는 0과 비교할 가능성이 있으므로
            //     if (
            //         null    === $this->attributes[$column]
            //         || null === $this->originAttributes[$column]
            //     ) {
            //         if (null !== $this->attributes[$column] && $this->attributes[$column] === $this->originAttributes[$column]) {
            //             continue;
            //         }
            //     } else {
            //         if (null !== $this->attributes[$column] && $this->attributes[$column] == $this->originAttributes[$column]) {
            //             continue;
            //         }
            //     }
            // }

            // raw가 아니고 값이 같으면 업데이트 안함
            // db에서 가져온것과 비교해서 바뀌지 않으면 업데이트 하지 말기

            if (false === isset($this->rawAttributes[$column])) {
                $attr     = $this->attributes[$column]       ?? null;
                $origAttr = $this->originAttributes[$column] ?? null;

                // attr과 originAttr가 같으면 continue
                if ($attr === $origAttr) {
                    // $this->sameColumns[$column] = $origAttr;

                    continue;
                }
            }

            if (true === isset($this->dataStyles[$column])
                && 'jsons' == $this->dataStyles[$column]) { // json을 jsons로 바꿈
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

    public function delete(bool $recursive = false)
    {
        if ($recursive) {
            return $this->objectToDelete();
        }

        return $this->doDelete();
    }

    private function iteratorToDelete(array|self $attributes)
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

    public function doDelete() : bool|self
    {
        if (true == $this->deleteLock) {
            return true;
        }

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
                $object->primaryKeyName => $object->attributes[$object->primaryKeyName],
            ];

            if (static::$debug) {
                $this->print($sql, $binds);
                Timer::start();
            }

            if ($this->getConnect()->set($sql, $binds)) {
                if (static::$debug) {
                    echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
                }
                $object->primaryKeyValue  = '';
                $object->attributes       = [];
                $object->originAttributes = [];
                unset($ojbect);
                $result = true;
            }
        }

        if ($result) {
            $this->primaryKeyValue  = '';
            $this->attributes       = [];
            $this->originAttributes = [];

            return $this;
        }

        return false;
    }

    private function getSelectColumns(string $prefixString = '', $isCount = false) : string
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

    public function groupBy(string $groupBy) : self
    {
        $this->groupBy = $groupBy;

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
        // exit;
    }

    private function buildCount(string $name, array $arguments, int $offset, $isGroup = false)
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

            $keyName = '';
        }

        if ($condition) {
            $condition = ' WHERE ' . $condition;
        }

        if (true === $isGroup) {
            $sql = <<<SQL
                SELECT
                    {$this->groupBy},
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

                foreach ($data as $index => &$row) {
                    if ($this->keyName) {
                        $attributes[$row[$this->keyName]] = new $class($this->getConnect(), $row);
                    } else {
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

    private function buildGetSum(string $name, array $arguments, int $offset) : float|int
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

    private function buildGetAvg(string $name, array $arguments, int $offset) : float|int
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

            $keyName = '';
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

            $join .= ' JOIN'
                   . PHP_EOL . '         `' . $tableName . '` AS `' . $tableAliasName . '`'
                   . PHP_EOL . '    ON'
                   . PHP_EOL . '        `' . $this->tableAliasName . '`.`' . $joinLeft . '` = `' . $tableAliasName . '`.`' . $joinRight . '`';
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

    public function gets(null|array|string $sql = null, array $binds = []) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';
        $keyName               = $this->keyName;

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

                $keyName = '';
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

                $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($row[$parentTableName]) {
                    $row[$parentTableName]->deleteLock = $joinModel->deleteLock;
                    // $row[$parentTableName]->parentNode = $joinModel->parentNode;
                }

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            // if (12803 == Di::getLoginUserModel(null)?->getSeq(0)) {
            //     foreach ($row as $r => $d) {
            //         if (false !== \strpos($r, '_seq')) {
            //             \pr($r);
            //             unset($row[$r]);
            //         }
            //     }
            // }

            if ($keyName) {
                if (false === \array_key_exists($this->keyName, $row)) {
                    throw new Exception('gets ' . $this->tableName . ' "' . $this->keyName . '" column not found #5');
                }

                $attributes[$row[$keyName]] = new $class($this->getConnect(), $row);
            } else {
                $attributes[] = new $class($this->getConnect(), $row);
            }
        }

        if ($attributes) {
            $attributes             = $this->getRelations($attributes);
            $this->attributes       = $attributes;
            $this->originAttributes = $this->attributes;

            return $this;
        }

        return $this->empty();
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

    public function get1(null|array|string $sql = null, array $binds = []) : ?self
    {
        throw new Exception('not support get1');
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

                $keyName = '';
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

                $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($attributes[$parentTableName]) {
                    $attributes[$parentTableName]->deleteLock = $joinModel->deleteLock;
                    // $attributes[$parentTableName]->parentNode = $joinModel->parentNode;
                }

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            $this->attributes       = $this->getRelation($attributes);
            $this->originAttributes = $this->attributes;

            $this->primaryKeyValue = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    public $parentNode = false;

    public function parentNode()
    {
        $this->parentNode = true;

        return $this;
    }

    private function buildForceIndex(string $name, array $arguments, int $offset = 3) : self
    {
        $key = \strtolower(\substr($name, $offset));

        return $this->forceIndex($key);
    }

    public function forceIndex(string $indexKey) : self
    {
        $this->forceIndexes[] = ' FORCE INDEX (`' . $indexKey . '`)';

        return $this;
    }

    private function buildNew(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        $this->attributes[$columnName] = $arguments[0];

        return $this;
    }

    // $model->setRawLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
    private function buildNewRaw(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 6));

        $this->rawAttributes[$columnName] = $arguments[0];
        $this->attributes[$columnName]    = $arguments[1] ?? null;

        return $this;
    }

    private function buildPlus(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 4));

        $this->attributes[$columnName]     = ($this->attributes[$columnName] ?? 0) + $arguments[0];
        $this->plusAttributes[$columnName] = $arguments[0];

        return $this;
    }

    private function buildMinus(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 5));

        $this->attributes[$columnName]      = ($this->attributes[$columnName] ?? 0) - $arguments[0];
        $this->minusAttributes[$columnName] = $arguments[0];

        return $this;
    }

    private function buildSet(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 3));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        if (false === \array_key_exists(0, $arguments)) {
            throw new Exception($columnName . ' not found.');
        }

        $this->attributes[$columnName] = $arguments[0];

        return $this;
    }

    // $model->setRawLocation('POINT(:x, :y)', [':x' => $geometry[0]['x'], ':y' => $geometry[0]['y']])
    private function buildSetRaw(string $name, array $arguments) : self
    {
        $columnName = \Limepie\decamelize(\substr($name, 6));

        if (false === \in_array($columnName, $this->allColumns, true)) {
            throw new Exception('set ' . $this->tableName . ' "' . $columnName . '" column not found #6');
        }

        $this->rawAttributes[$columnName] = $arguments[0];
        $this->attributes[$columnName]    = $arguments[1] ?? null;

        return $this;
    }

    public $groupBy;

    private function buildGroupBy(string $groupByString, array $arguments) : self
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

    private function buildOrderBy(string $orderByString, array $arguments) : self
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

    private function buildAvg(string $name, array $arguments, int $offset = 3) : self
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

    private function buildAnd(string $name, array $arguments, int $offset = 3) : self
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

    private function buildOr(string $name, array $arguments, int $offset = 2) : self
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

    private function xbuildOr(string $name, array $arguments = [], int $offset = 2) : self
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
    private function getConditions(string $name, array $arguments, int $offset = 0) : array
    {
        if (false === \strpos($name, ' ')) {
            $splitKeys = $this->splitKey($name, $offset);
            $binds     = [];
            $conds     = [];

            foreach ($splitKeys as $index => $splitKey) {
                ++$this->bindcount;

                [$open, $key, $close, $operator] = $splitKey;

                $bindKeyname = $this->tableAliasName . '_' . $key . '_' . $this->bindcount;

                if (true === \is_object($arguments)) {
                    throw (new Exception($this->tableName . ' ' . $key . ' argument error'))->setDebugMessage('page not found', __FILE__, __LINE__);
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

        //    \pr($this->keyName);

        return $this;
    }

    private function buildAlias(string $name, array $arguments) : self
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

    private function buildMatch(string $name, $arguments) : self
    {
        if (1 === \preg_match('#match(All)?(?P<leftKeyName>.*)With(?P<rightKeyName>.*)$#U', $name, $m)) {
            $this->leftKeyName  = \Limepie\decamelize($m['leftKeyName']);
            $this->rightKeyName = \Limepie\decamelize($m['rightKeyName']);
        } else {
            throw new Exception('"' . $name . '" syntax error', 1999);
        }

        return $this;
    }

    private function buildGetBy(string $name, array $arguments, int $offset) : ?self
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

            $keyName = '';
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

                $attributes[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($attributes[$parentTableName]) {
                    $attributes[$parentTableName]->deleteLock = $joinModel->deleteLock;
                    // $attributes[$parentTableName]->parentNode = $joinModel->parentNode;
                }

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            $this->attributes       = $this->getRelation($attributes);
            $this->originAttributes = $this->attributes;
            $this->primaryKeyValue  = $this->attributes[$this->primaryKeyName] ?? null;

            return $this;
        }

        return $this->empty();
    }

    private function buildGetsBy(string $name, array $arguments, int $offset) : ?self
    {
        $this->attributes      = [];
        $this->primaryKeyValue = '';

        [$condition, $binds] = $this->getConditionAndBinds($name, $arguments, $offset);

        $selectColumns = $this->getSelectColumns();
        $condition .= $this->condition;
        $binds += $this->binds;

        $orderBy    = $this->getOrderBy();
        $limit      = $this->getLimit();
        $join       = '';
        $forceIndex = \implode(', ', $this->forceIndexes);
        $keyName    = $this->keyName;

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

        $data = $this->getConnect()->gets($sql, $binds, false);

        if (static::$debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        $attributes = [];

        $class = \get_called_class();

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

                $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp);

                if ($row[$parentTableName]) {
                    $row[$parentTableName]->deleteLock = $joinModel->deleteLock;
                    // $row[$parentTableName]->parentNode = $joinModel->parentNode;
                }

                if ($joinModel->oneToOne) {
                    $this->oneToOnes[$parentTableName] = $joinModel->oneToOne;
                }

                if ($joinModel->oneToMany) {
                    $this->oneToManies[$parentTableName] = $joinModel->oneToMany;
                }
            }

            if ($keyName) {
                if (false === \array_key_exists($this->keyName, $row)) {
                    \pr($keyName, $this->keyName, $row);

                    throw new Exception('gets by ' . $this->tableName . ' "' . $keyName . '" column not found #7');
                }

                $attributes[$row[$keyName]] = new $class($this->getConnect(), $row);
            } else {
                $attributes[$row[$this->primaryKeyName]] = new $class($this->getConnect(), $row);
            }
        }
        unset($row);

        if ($attributes) {
            $this->attributes       = $this->getRelations($attributes);
            $this->originAttributes = $this->attributes;

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
}
