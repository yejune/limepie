<?php

declare(strict_types=1);

namespace Limepie\Model;

use Limepie\Model;
use Limepie\ArrayObject;
use Limepie\Exception;

/**
 * 모델 관계 처리 전담 클래스
 * - oneToOne, oneToMany 관계 처리
 * - 조인 데이터 처리
 * - 관계 데이터 로딩
 */
class RelationManager
{
    /**
     * 단일 모델의 관계 데이터 처리
     */
    public static function getRelation(array $attributes, Model $model): array
    {
        // oneToOne 관계 처리
        if ($model->oneToOne) {
            foreach ($model->oneToOne as $class) {
                $attributes = self::getRelationData(
                    $class,
                    $attributes,
                    $model,
                    functionName: 'getBy',
                    isSingle: true
                );
            }
        }

        // oneToOnes 관계 처리 (조인에서 온 것)
        if ($model->oneToOnes ?? false) {
            foreach ($model->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    $attributes = self::getRelationData(
                        $class,
                        $attributes,
                        $model,
                        parentTableName: $parentTableName,
                        functionName: 'getBy',
                        isSingle: true
                    );
                }
            }
        }

        // oneToMany 관계 처리
        if ($model->oneToMany) {
            foreach ($model->oneToMany as $class) {
                $attributes = self::getRelationData(
                    $class,
                    $attributes,
                    $model,
                    functionName: 'getsBy',
                    isSingle: false
                );
            }
        }

        // oneToManies 관계 처리 (조인에서 온 것)
        if ($model->oneToManies ?? false) {
            foreach ($model->oneToManies as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    $attributes = self::getRelationData(
                        $class,
                        $attributes,
                        $model,
                        parentTableName: $parentTableName,
                        functionName: 'getsBy',
                        isSingle: false
                    );
                }
            }
        }

        return $attributes;
    }

    /**
     * 복수 모델의 관계 데이터 처리
     */
    public static function getRelations(array $attributes, Model $model): array
    {
        // oneToOne 관계 처리
        if ($model->oneToOne) {
            foreach ($model->oneToOne as $class) {
                $attributes = self::getRelationsData(
                    $class,
                    $attributes,
                    functionName: 'getsBy',
                    isSingle: true
                );
            }
        }

        // oneToOnes 관계 처리
        if ($model->oneToOnes ?? false) {
            foreach ($model->oneToOnes as $parentTableName => $oneToOne) {
                foreach ($oneToOne as $class) {
                    $attributes = self::getRelationsData(
                        $class,
                        $attributes,
                        functionName: 'getsBy',
                        parentTableName: $parentTableName,
                        isSingle: true
                    );
                }
            }
        }

        // oneToMany 관계 처리
        if ($model->oneToMany) {
            foreach ($model->oneToMany as $class) {
                $attributes = self::getRelationsData(
                    $class,
                    $attributes,
                    functionName: 'getsBy',
                    isSingle: false
                );
            }
        }

        // oneToManies 관계 처리
        if ($model->oneToManies ?? []) {
            foreach ($model->oneToManies as $parentTableName => $oneToMany) {
                foreach ($oneToMany as $class) {
                    $attributes = self::getRelationsData(
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

    /**
     * 단일 관계 데이터 로딩
     */
    public static function getRelationData(
        Model $class,
        array $attribute,
        Model $parentModel,
        string $functionName,
        string $parentTableName = '',
        bool $isSingle = true
    ): array {
        $connect = $class->pdo ?: $parentModel->getConnect();

        $leftKeyName = $class->leftKeyName ?: $class->primaryKeyName;
        $rightKeyName = $class->rightKeyName ?: $class->tableName . '_' . $class->primaryKeyName;

        // single일때 keyName을 바꾸는건 사실상 적용되지 않는다. 결과물이 1개이다.
        if ($isSingle) {
            $class->keyName = $rightKeyName;
        }

        $row = $parentTableName ? $attribute[$parentTableName] : $attribute;

        if ($row instanceof Model) {
            $row = $row->toArray();
        }

        if (!array_key_exists($leftKeyName, $row)) {
            throw new Exception("{$class->tableName}: Undefined left key '{$leftKeyName}'");
        }

        // 관계 조건 확인
        if (($class->possibleRelationKey ?? false) && isset($attribute[$class->possibleRelationKey])) {
            if ($attribute[$class->possibleRelationKey] !== $class->possibleRelationValue) {
                return $attribute;
            }
        }

        // 함수 호출을 위한 인수 준비
        $args = [$row[$leftKeyName]];

        foreach ($class->and as $key => $value) {
            $functionName .= 'And' . \Limepie\camelize($key);
            $args[] = $value;
        }

        $functionName .= \Limepie\camelize($rightKeyName);
        $data = call_user_func_array([$class($connect), $functionName], $args);

        if ($data instanceof Model) {
            $data->deleteLock = $class->deleteLock;
        }

        // 모듈명 결정
        $moduleName = $class->newTableName ?: ($isSingle ? $class->tableName . '_model' : $class->tableName . '_models');

        // 데이터 할당
        if ($parentTableName) {
            if ($class->parentNode && $isSingle) {
                foreach ($data ?? [] as $key => $value) {
                    if ($key !== 'seq' && self::isMoveParent($attribute[$parentTableName], $key, $value)) {
                        $attribute[$parentTableName][$key] = $value;
                    }
                }
            } else {
                $attribute[$parentTableName][$moduleName] = $data;
            }
        } else {
            if ($class->parentNode && $isSingle) {
                foreach ($data ?? [] as $key => $value) {
                    if ($key !== 'seq' && self::isMoveParent($attribute, $key, $value)) {
                        $attribute[$key] = $value;
                    }
                }
            } else {
                $attribute[$moduleName] = $data;
            }
        }

        return $attribute;
    }

    /**
     * 복수 관계 데이터 로딩
     */
    public static function getRelationsData(
        Model $class,
        array $attributes,
        string $functionName,
        string $parentTableName = '',
        bool $isSingle = true
    ): array {
        $seqs = [];
        $data = [];

        $leftKeyName = $class->leftKeyName ?: $class->primaryKeyName;
        $rightKeyName = $class->rightKeyName ?: $class->tableName . '_' . $class->primaryKeyName;

        // keyName 매핑을 위한 임시 저장
        $remapKey = $class->keyName;

        if ($isSingle) {
            $class->keyName = $rightKeyName;
        } else {
            $class->keyName = $class->primaryKeyName;
        }

        $connect = $class->pdo ?: $attributes[array_key_first($attributes)]->getConnect();

        // 모든 seq 수집
        foreach ($attributes as $attribute) {
            $row = $parentTableName ? $attribute[$parentTableName] : $attribute;

            if ($row instanceof Model) {
                $row = $row->toArray();
            }

            if (array_key_exists($leftKeyName, $row)) {
                if ($row[$leftKeyName] !== null) {
                    if (($class->possibleRelationKey ?? false) && isset($attribute[$class->possibleRelationKey])) {
                        if ($attribute[$class->possibleRelationKey] !== $class->possibleRelationValue) {
                            continue;
                        }
                    }

                    $seqs[] = $row[$leftKeyName];
                }
            } else {
                throw new Exception("Column '{$leftKeyName}' not found");
            }
        }

        // 데이터 로딩
        if ($seqs) {
            $seqs = array_unique($seqs);
            $functionName .= \Limepie\camelize($rightKeyName);
            $args = [$seqs];

            foreach ($class->and as $key => $value) {
                $functionName .= 'And' . \Limepie\camelize($key);
                $args[] = $value;
            }

            $data = call_user_func_array([$class($connect), $functionName], $args);
        }

        // 모듈명 결정
        $moduleName = $class->newTableName ?: ($isSingle ? $class->tableName . '_model' : $class->tableName . '_models');

        // 데이터 할당 (상세 로직은 원본 코드와 동일)
        return self::assignRelationData($attributes, $data, $class, $moduleName, $leftKeyName, $rightKeyName, $parentTableName, $isSingle, $remapKey, $connect);
    }

    /**
     * 부모로 값 이동 가능 여부 확인
     */
    public static function isMoveParent(array $data, string $key, mixed $value): bool
    {
        // null이 아닌 경우: 무조건 이동 가능
        if ($value !== null) {
            return true;
        }

        // null이고 키가 존재하지 않는 경우: 이동 가능
        if (!isset($data[$key])) {
            return true;
        }

        // null이고 키가 이미 존재하는 경우: 이동 불가
        return false;
    }

    /**
     * 관계 데이터 할당 (원본 ModelUtil.php 로직 완전 구현)
     */
    private static function assignRelationData(
        array $attributes,
        array $data,
        Model $class,
        string $moduleName,
        string $leftKeyName,
        string $rightKeyName,
        string $parentTableName,
        bool $isSingle,
        string $remapKey,
        \PDO $connect
    ): array {
        if ($isSingle) {
            // Single 관계 처리
            foreach ($attributes as $attribute) {
                $leftKeyValue = $parentTableName ? 
                    ($attribute[$parentTableName][$leftKeyName] ?? false) : 
                    ($attribute[$leftKeyName] ?? false);

                if ($leftKeyValue && isset($data[$leftKeyValue]) && $data[$leftKeyValue]) {
                    $data[$leftKeyValue]->deleteLock = $class->deleteLock;

                    // match 키 제거 처리
                    if (!$class->matchKeyRemove && $rightKeyName !== 'seq') {
                        unset($data[$leftKeyValue][$rightKeyName]);
                    }
                }

                // 데이터 할당
                if ($parentTableName) {
                    if ($class->parentNode) {
                        foreach ($data[$leftKeyValue] ?? [] as $key => $value) {
                            if ($key !== 'seq' && self::isMoveParent($attribute[$parentTableName], $key, $value)) {
                                $attribute[$parentTableName][$key] = $value;
                            }
                        }
                    } else {
                        $attribute[$parentTableName]->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    }
                } else {
                    if ($class->parentNode) {
                        foreach ($data[$leftKeyValue] ?? [] as $key => $value) {
                            if ($key !== 'seq' && self::isMoveParent($attribute, $key, $value)) {
                                $attribute[$key] = $value;
                            }
                        }
                    } else {
                        $attribute->offsetSet($moduleName, $data[$leftKeyValue] ?? null);
                    }
                }
            }
        } else {
            // Many 관계 처리 - 원본 로직 완전 구현
            try {
                // right에 primary key name으로 relation[s]으로 매칭되는 배열을 만든다.
                $rightKeyMapValues = [];

                foreach ($data ?? [] as $key => $row) {
                    $keyName = $row->originAttributes[$rightKeyName];
                    $rightKeyMapValues[$keyName][$key] = $row;
                }
            } catch (\Throwable $e) {
                throw $e;
            }

            foreach ($attributes as $attribute) {
                $leftKeyValue = $parentTableName ? 
                    ($attribute[$parentTableName][$leftKeyName] ?? '') : 
                    ($attribute[$leftKeyName] ?? '');

                // left와 매칭되는 값이 있을때
                if ($leftKeyValue && isset($rightKeyMapValues[$leftKeyValue])) {
                    if ($class->keyName === $remapKey) { 
                        // 같으므로 remap할 필요가 없다.
                        $rightKeyMapValueByLeftKey = $rightKeyMapValues[$leftKeyValue];
                    } else {
                        // keyName 리매핑
                        $rightKeyMapValueByLeftKey = [];

                        foreach ($rightKeyMapValues[$leftKeyValue] as $key => $value) {
                            if ($remapKey instanceof \Closure) {
                                $innerKeyName = ($remapKey)($value->originAttributes);
                            } else {
                                $innerKeyName = $value->originAttributes[$remapKey];
                            }
                            $rightKeyMapValueByLeftKey[$innerKeyName] = $value;
                        }
                    }

                    // 새 인스턴스 생성 (원본과 동일한 방식)
                    $instance = new $class($connect, $rightKeyMapValueByLeftKey);
                    $instance->deleteLock = $class->deleteLock;

                    // 데이터 할당
                    if ($parentTableName) {
                        if ($class->parentNode) { 
                            // parent로 옮길때는 seq까지 옮기면 덮어 쓴다.
                            foreach ($rightKeyMapValueByLeftKey ?? [] as $key => $value) {
                                if ('seq' !== $key && self::isMoveParent($attribute[$parentTableName], $key, $value)) {
                                    $attribute[$parentTableName][$key] = $value;
                                }
                            }
                        } else {
                            $attribute[$parentTableName]->offsetSet($moduleName, $instance);
                        }
                    } else {
                        if ($class->parentNode) { 
                            // parent로 옮길때는 seq까지 옮기면 덮어 쓴다.
                            foreach ($rightKeyMapValueByLeftKey ?? [] as $key => $value) {
                                if ('seq' !== $key && self::isMoveParent($attribute, $key, $value)) {
                                    $attribute[$key] = $value;
                                }
                            }
                        } else {
                            $attribute->offsetSet($moduleName, $instance);
                        }
                    }
                } else {
                    // 매칭되는 데이터가 없는 경우 null 할당
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
}