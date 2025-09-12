<?php

declare(strict_types=1);

namespace Limepie\Model;

use Limepie\ArrayObject;
use Limepie\Timer;

/**
 * SQL 실행 및 결과 처리 전담 클래스
 * - get, gets 실행
 * - 결과 데이터 가공
 * - 조인 데이터 처리
 */
class SqlExecutor
{
    /**
     * SELECT 쿼리 빌드
     */
    public static function buildSelectQuery(
        string $tableName,
        string $tableAliasName,
        array $selectColumns,
        string $condition,
        array $and,
        string $orderBy,
        ?int $limit,
        ?int $offset,
        array $joinModels,
        array $forceIndexes
    ): string {
        // SELECT 절 생성 - 각 컬럼에 테이블 alias 적용
        $selectParts = [];
        
        if (empty($selectColumns) || $selectColumns === ['*']) {
            // * 선택 시에도 테이블 alias 적용하지 않고 * 사용
            $selectParts[] = '*';
        } else {
            // 각 컬럼에 테이블 alias 적용
            foreach ($selectColumns as $column) {
                if ($column === '*') {
                    $selectParts[] = '*';
                } else {
                    $selectParts[] = "`{$tableAliasName}`.`{$column}`";
                }
            }
        }
        
        $selectClause = implode(', ', $selectParts);
        $sql = "SELECT {$selectClause} FROM `{$tableName}` AS `{$tableAliasName}`";
        
        // WHERE 절
        if (!empty($condition)) {
            $sql .= " WHERE " . $condition;
        }
        
        // ORDER BY 절
        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $orderBy;
        }
        
        // LIMIT 절
        if ($limit !== null) {
            if ($offset !== null) {
                $sql .= " LIMIT {$offset}, {$limit}";
            } else {
                $sql .= " LIMIT {$limit}";
            }
        }
        
        return $sql;
    }

    /**
     * 단일 행 조회 실행
     */
    public static function executeGet(
        \PDO $connection,
        string $sql, 
        array $binds = [],
        array $callbackColumns = [],
        array $joinModels = [],
        array $dataStyles = [],
        bool $debug = false
    ): ?array {
        if ($debug) {
            Timer::start();
        }

        $attributes = $connection->get($sql, $binds, false);

        // 콜백 컬럼 처리
        foreach ($callbackColumns as $callbackColumn) {
            $attributes[$callbackColumn['alias']] = $callbackColumn['callback']($attributes[$callbackColumn['column']]);
        }

        if ($debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        if (!$attributes) {
            return null;
        }

        // 데이터 타입 처리
        $attributes = DataProcessor::buildDataTypes($attributes, $dataStyles);

        // 조인 모델 처리
        $attributes = self::processJoinModels($attributes, $joinModels, $connection);

        return $attributes;
    }

    /**
     * 복수 행 조회 실행
     */
    public static function executeGets(
        \PDO $connection,
        string $sql,
        array $binds = [],
        array $callbackColumns = [],
        array $joinModels = [],
        array $dataStyles = [],
        string $keyName = '',
        string $primaryKeyName = 'seq',
        bool $debug = false
    ): array {
        if ($debug) {
            Timer::start();
        }

        $data = $connection->gets($sql, $binds, false);

        if ($debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        if (!$data) {
            return [];
        }

        $class = get_called_class(); // 호출한 모델 클래스
        $attributes = [];

        foreach ($data as $index => &$row) {
            // 콜백 컬럼 처리
            foreach ($callbackColumns as $callbackColumn) {
                $row[$callbackColumn['alias']] = $callbackColumn['callback']($row[$callbackColumn['column']]);
            }

            // 조인 모델 처리
            $row = self::processJoinModels($row, $joinModels, $connection);

            // 키 이름 결정
            if ($keyName) {
                if ($keyName instanceof \Closure) {
                    $keyValue = ($keyName)($row);
                } else {
                    if (!array_key_exists($keyName, $row)) {
                        throw new \Exception("gets column '{$keyName}' not found");
                    }
                    $keyValue = $row[$keyName];
                }
            } else {
                $keyValue = $row[$primaryKeyName];
            }

            $attributes[$keyValue] = $row; // 실제로는 모델 인스턴스 생성 필요
        }

        return $attributes;
    }

    /**
     * 조인된 모델 데이터 처리
     */
    private static function processJoinModels(array $attributes, array $joinModels, \PDO $connection): array
    {
        foreach ($joinModels as $joinModelInfo) {
            $joinModel = $joinModelInfo['model'];
            $joinClassAliasName = $joinModel->tableAliasName;

            $tmp = [];

            // 조인된 컬럼들을 분리
            foreach ($attributes as $fieldName => $fieldValue) {
                if (strpos($fieldName, $joinClassAliasName . '_') === 0) {
                    $cleanFieldName = str_replace($joinClassAliasName . '_', '', $fieldName);
                    $tmp[$cleanFieldName] = $fieldValue;
                    unset($attributes[$fieldName]);
                }
            }

            // 부모 테이블 이름 결정
            $parentTableName = $joinModel->newTableName ?: $joinModel->tableName . '_model';

            if ($joinModel->parentNode) {
                // parentNode가 true일 경우, 부모에게 자식 데이터를 병합
                foreach ($tmp as $key => $value) {
                    if ($key !== 'seq' && self::canMoveToParent($attributes, $key, $value)) {
                        $attributes[$key] = $value;
                    }
                }
            } else {
                // 별도 객체로 생성
                $attributes[$parentTableName] = $tmp; // 실제로는 조인 모델 인스턴스 생성 필요
            }
        }

        return $attributes;
    }

    /**
     * 값을 부모로 이동할 수 있는지 확인
     */
    private static function canMoveToParent(array $data, string $key, mixed $value): bool
    {
        // null이 아닌 값은 항상 이동 가능
        if ($value !== null) {
            return true;
        }

        // null인데 키가 존재하지 않으면 이동 가능
        if (!isset($data[$key])) {
            return true;
        }

        // null인데 키가 이미 존재하면 이동 불가
        return false;
    }

    /**
     * COUNT 쿼리 실행
     */
    public static function executeCount(
        \PDO $connection,
        string $sql,
        array $binds = [],
        bool $isGroup = false,
        bool $debug = false
    ): int|array {
        if ($debug) {
            Timer::start();
        }

        if ($isGroup) {
            $data = $connection->gets($sql, $binds, false);
            
            if ($debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }

            $attributes = [];
            foreach ($data as $row) {
                $attributes[] = $row; // 실제로는 모델 인스턴스 생성 필요
            }
            
            return $attributes;
        } else {
            $result = $connection->get1($sql, $binds, false);
            
            if ($debug) {
                echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
            }

            return (int) $result;
        }
    }

    /**
     * SUM 쿼리 실행
     */
    public static function executeSum(
        \PDO $connection,
        string $sql,
        array $binds = [],
        bool $debug = false
    ): float|int {
        if ($debug) {
            Timer::start();
        }

        $data = $connection->get1($sql, $binds, false);

        if ($debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        return \Limepie\decimal($data);
    }

    /**
     * AVG 쿼리 실행
     */
    public static function executeAvg(
        \PDO $connection,
        string $sql,
        array $binds = [],
        bool $debug = false
    ): float|int {
        if ($debug) {
            Timer::start();
        }

        $data = $connection->get1($sql, $binds, false);

        if ($debug) {
            echo '<div style="font-size: 9pt;">ㄴ ' . Timer::stop() . '</div>';
        }

        return \Limepie\decimal($data);
    }
}