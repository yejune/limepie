<?php

declare(strict_types=1);

namespace Limepie\Model\Base;

use ArrayObject;
use Limepie\Exception;

/**
 * Limepie ORM 핵심 클래스 - 84개 속성과 기본 기능 제공
 * 
 * ArrayObject 상속으로 배열/객체 호환 인터페이스 제공:
 * - $model['name'] 또는 $model->name 모두 지원
 * - foreach, count, isset 등 배열 함수 모두 사용 가능
 * - JSON 직렬화 자동 지원
 * 
 * 주요 속성 그룹:
 * 1. 데이터베이스 연결: pdo, tableName, primaryKeyName
 * 2. 데이터 처리: dataStyles, attributes, originAttributes  
 * 3. 쿼리 빌딩: condition, binds, orderBy, limit
 * 4. 관계 설정: oneToOne, oneToMany, leftKeyName, rightKeyName
 * 5. 고급 기능: joinModels, forceIndexes, sumColumn
 * 
 * @package Limepie\Model\Base
 */
abstract class Core extends ArrayObject
{
    // === 기본 속성들 ===
    public $pdo;
    public $dataStyles = [];
    public $dataTypes = [];
    public $tableName;
    public $newTableName;
    public $tableAliasName;
    public $primaryKeyName = 'seq';
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
    public $valueName;
    public $offset;
    public $limit;
    public $groupLimit;
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
    public static $debugBind = false;
    public $parentNode = false;
    public $parentNodeFlag = 0;
    public $callbackColumns = [];
    public $changeColumns = [];
    public $changeBinds = [];
    public $onCondition = '';
    public $onConditionBinds = [];
    public $sameColumns = [];
    public $groupBy;
    public $groupKey;
    public $joinTableAliasName;
    public $duplication;
    public $possibleRelationKey = '';
    public $possibleRelationValue = '';
    public $isRemoveAllColumn = false;
    public $addColumns = [];
    public $joinTableIndexs = [];
    public $rawColumnAliasNames = [];
    public $fkColumns = [];

    /**
     * 테스트 시 외부에서 주입할 수 있는 테이블 alias 생성 함수
     */
    private static $tableAliasNameFunction = null;

    public function __construct(?\PDO $pdo = null, $attributes = null, $originAttributes = [])
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $this->setAttributes($attributes);
        }

        if ($originAttributes) {
            $this->setOriginAttributes($originAttributes);
        }

        $this->keyName        = $this->primaryKeyName;
        $this->tableAliasName = $this->generateTableAliasName();
    }

    /**
     * 테이블 alias 이름 생성
     * 테스트에서 외부 주입 가능, 기본적으로는 원래 alias에 카운터 추가
     */
    private function generateTableAliasName(): string
    {
        if (self::$tableAliasNameFunction !== null) {
            return call_user_func(self::$tableAliasNameFunction);
        }

        if (!isset($_SERVER['db_instance_count'])) {
            $_SERVER['db_instance_count'] = 0;
        }

        return $this->tableAliasName . (++$_SERVER['db_instance_count']);
    }

    /**
     * 테스트용: 커스텀 테이블 alias 이름 생성 함수 설정
     */
    public static function setTableAliasNameFunction(?callable $function = null): void
    {
        self::$tableAliasNameFunction = $function;
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

    // === 기본 속성 관리 메서드들 ===
    
    public function setConnect(\PDO $pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }

    public function getConnect(): ?\PDO
    {
        return $this->pdo;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = is_array($attributes) ? $attributes : [];
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setOriginAttributes($originAttributes)
    {
        $this->originAttributes = is_array($originAttributes) ? $originAttributes : [];
        return $this;
    }

    public function getOriginAttributes(): array
    {
        return $this->originAttributes;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function hasAttribute($key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function removeAttribute($key)
    {
        unset($this->attributes[$key]);
        return $this;
    }

    public function clearAttributes()
    {
        $this->attributes = [];
        return $this;
    }

    public function offsetGet($offset): mixed
    {
        $target = $this->attributes;
        if (false === \array_key_exists($offset, $target)) {
            $traces = \debug_backtrace();
            foreach ($traces as $trace) {
                if (true === isset($trace['file'])) {
                    if (false === \in_array($offset, $this->allColumns, true)) {
                        $message = 'Undefined offset: ' . $offset;
                        $code    = 400234;
                    } else {
                        $message = 'offset ' . $offset . ' is null';
                        $code    = 400123;
                    }
                    $filename = $trace['file'];
                    $line     = $trace['line'];
                    $e = (new Exception($message, $code))->setDebugMessage($message, __FILE__, __LINE__);
                    throw $e;
                    break;
                }
            }
        }
        return $target[$offset] ?? null;
    }

    public function buildDataType(array|string $attributes = [])
    {
        if (\is_array($attributes)) {
            return DataProcessor::buildDataTypes($attributes, $this->dataStyles);
        }
        return $attributes;
    }

    public static function newInstance(?\PDO $pdo = null, $attributes = null): self
    {
        return new static($pdo, $attributes);
    }

    public function getmicrotime()
    {
        return \microtime(true);
    }

    public function getOrderBy()
    {
        return $this->orderBy ? "ORDER BY {$this->orderBy}" : '';
    }

    public function getLimit()
    {
        if ($this->limit !== null) {
            if ($this->offset !== null) {
                return "LIMIT {$this->offset}, {$this->limit}";
            } else {
                return "LIMIT {$this->limit}";
            }
        }
        return '';
    }

    public static function debug(?string $filename = null, null|int|string $line = null)
    {
        static::$debug = true;
        if (!$filename) {
            $trace = \debug_backtrace()[0];
            $filename = $trace['file'];
            $line = $trace['line'];
        }
        echo '<div style="color: red; font-weight: bold;">Debug enabled at ' . $filename . ':' . $line . '</div>';
    }

    public function print($sql = null, $binds = null)
    {
        if ($sql) {
            echo '<pre style="background: #f0f0f0; padding: 10px; margin: 5px;">';
            echo htmlspecialchars($sql);
            if ($binds) {
                echo "\nBinds: " . print_r($binds, true);
            }
            echo '</pre>';
        }
    }

    public function empty()
    {
        $this->attributes = [];
        $this->originAttributes = [];
        return $this;
    }

    public function close()
    {
        return $this;
    }

    public function deleteLock($flag = true): self
    {
        $this->deleteLock = $flag;
        return $this;
    }

    public function getDeleteLock()
    {
        return $this->deleteLock;
    }

    public function helper(): self
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
        echo "<pre>" . implode("\n", $conditions) . "</pre>";
        return $this;
    }

    public function escapeFunction($value)
    {
        if (is_string($value)) {
            return addslashes($value);
        }
        return $value;
    }

    public function isValidSeqArgument(string $operator, mixed $argument): bool
    {
        if ('seq' !== \strtolower(\substr($operator, -3))) {
            return true; // Not a Seq operator, so it's valid
        }
        return is_numeric($argument) && $argument > 0;
    }

    public function function($bind, $extraCondition = '', $extraBinds = [])
    {
        // function 메서드 - 특수한 바인딩 처리
        $this->binds = array_merge($this->binds, (array)$bind);
        if ($extraCondition) {
            $this->condition .= ' ' . $extraCondition;
            if ($extraBinds) {
                $this->binds = array_merge($this->binds, $extraBinds);
            }
        }
        return $this;
    }
}