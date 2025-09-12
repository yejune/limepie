<?php

declare(strict_types=1);

namespace Limepie\Model;

use Limepie\Exception;
use Limepie\Aes;

/**
 * SQL 쿼리 생성 전담 클래스
 * - Create/Update SQL 생성
 * - 조건문 생성
 * - 바인드 파라미터 처리
 */
class QueryBuilder
{
    /**
     * CREATE용 컬럼, 값, 바인드 생성
     */
    public static function buildCreateComponents(
        array $allColumns,
        array $attributes,
        array $dataStyles,
        ?string $sequenceName,
        string $tableName,
        string $prefix = '',
        array $rawAttributes = []
    ): array {
        $columns = [];
        $binds = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $columnBindName = $prefix . $column;
            
            // 시퀀스 컬럼은 건너뛰기
            if ($sequenceName === $column) {
                continue;
            }

            // IP 컬럼 처리
            if ($column === 'ip') {
                $columns[] = "`{$column}`";
                $binds[":{$columnBindName}"] = $attributes[$column] ?? \Limepie\getIp();
                $values[] = "inet6_aton(:{$columnBindName})";
                continue;
            }

            // AES 암호화 처리
            if (self::isAesColumn($dataStyles, $column)) {
                $result = self::buildAesCreate($column, $columnBindName, $dataStyles[$column], $attributes);
                $columns[] = $result['column'];
                $binds = array_merge($binds, $result['binds']);
                $values[] = $result['value'];
                continue;
            }

            // Point 타입 처리
            if (self::isPointColumn($dataStyles, $column, $attributes)) {
                $result = self::buildPointCreate($column, $columnBindName, $attributes[$column]);
                $columns[] = $result['column'];
                $binds = array_merge($binds, $result['binds']);
                $values[] = $result['value'];
                continue;
            }

            // 일반 속성 처리
            if (array_key_exists($column, $attributes)) {
                $result = self::buildRegularCreate($column, $columnBindName, $attributes, $dataStyles, $tableName, $rawAttributes);
                if ($result) {
                    $columns[] = $result['column'];
                    $binds = array_merge($binds, $result['binds']);
                    $values[] = $result['value'];
                }
            }
        }

        return [$columns, $binds, $values];
    }

    /**
     * UPDATE용 컬럼, 바인드 생성
     */
    public static function buildUpdateComponents(
        array $allColumns,
        array $attributes,
        array $originAttributes,
        array $dataStyles,
        array $plusAttributes,
        array $minusAttributes,
        array $rawAttributes,
        ?string $sequenceName,
        string $tableName,
        string $prefix = ''
    ): array {
        $columns = [];
        $binds = [];
        $sames = [];

        foreach ($allColumns as $column) {
            $columnBindName = $prefix . $column;
            
            $attr = $attributes[$column] ?? null;
            $origAttr = $originAttributes[$column] ?? null;

            // 변경되지 않은 값은 건너뛰기
            if (self::shouldSkipUpdate($attr, $origAttr, $plusAttributes, $minusAttributes, $rawAttributes, $column, $originAttributes)) {
                $sames[$column] = $origAttr;
                continue;
            }

            // jsons 특별 처리
            if (self::shouldSkipJsonsUpdate($dataStyles, $column, $attributes, $originAttributes)) {
                continue;
            }

            // 시퀀스 컬럼 건너뛰기
            if ($sequenceName === $column) {
                continue;
            }

            $result = self::buildUpdateColumn($column, $columnBindName, $attributes, $dataStyles, $plusAttributes, $minusAttributes, $rawAttributes, $tableName);
            if ($result) {
                $columns[] = $result['column'];
                $binds = array_merge($binds, $result['binds']);
            }
        }

        return [$columns, $binds, $sames];
    }

    // === 헬퍼 메서드들 ===

    private static function isTimestampColumn(string $column): bool
    {
        return in_array($column, ['created_ts', 'updated_ts']);
    }

    private static function isAesColumn(array $dataStyles, string $column): bool
    {
        return isset($dataStyles[$column]) && 
               in_array($dataStyles[$column], ['aes_serialize', 'aes', 'aes_hex']);
    }

    private static function isPointColumn(array $dataStyles, string $column, array $attributes): bool
    {
        return isset($dataStyles[$column]) && 
               $dataStyles[$column] === 'point' && 
               isset($attributes[$column]) && 
               is_array($attributes[$column]);
    }

    private static function buildAesCreate(string $column, string $columnBindName, string $dataStyle, array $attributes): array
    {
        $value = $attributes[$column] ?? null;
        
        $binds = [
            ":{$columnBindName}" => $dataStyle === 'aes_serialize' ? \serialize($value) : $value,
            ":{$columnBindName}_secretkey" => Aes::$salt
        ];

        return [
            'column' => "`{$column}`",
            'binds' => $binds,
            'value' => DataProcessor::buildAesEncryptSql($dataStyle, $columnBindName, "{$columnBindName}_secretkey")
        ];
    }

    private static function buildPointCreate(string $column, string $columnBindName, array $pointValue): array
    {
        if (is_null($pointValue)) {
            throw new Exception('empty point value');
        }

        return [
            'column' => "`{$column}`",
            'binds' => [
                ":{$columnBindName}1" => $pointValue[0],
                ":{$columnBindName}2" => $pointValue[1]
            ],
            'value' => "point(:{$columnBindName}1, :{$columnBindName}2)"
        ];
    }

    private static function buildRegularCreate(string $column, string $columnBindName, array $attributes, array $dataStyles, string $tableName, array $rawAttributes = []): ?array
    {
        $value = $attributes[$column];

        // Raw 속성 처리
        if (isset($rawAttributes[$column])) {
            return [
                'column' => "`{$tableName}`.`{$column}`",
                'binds' => is_array($value) ? $value : [],
                'value' => str_replace('?', ":{$column}", $rawAttributes[$column])
            ];
        }

        // 데이터 스타일 처리
        if (isset($dataStyles[$column])) {
            $value = DataProcessor::processForStorage($dataStyles[$column], $value);
        }

        return [
            'column' => "`{$column}`",
            'binds' => [":{$columnBindName}" => $value],
            'value' => ":{$columnBindName}"
        ];
    }

    private static function shouldSkipUpdate($attr, $origAttr, array $plusAttributes, array $minusAttributes, array $rawAttributes, string $column, ?array $originAttributes): bool
    {
        return $originAttributes && 
               $attr === $origAttr && 
               !isset($plusAttributes[$column]) && 
               !isset($minusAttributes[$column]) && 
               !isset($rawAttributes[$column]);
    }

    private static function shouldSkipJsonsUpdate(array $dataStyles, string $column, array $attributes, array $originAttributes): bool
    {
        if (!isset($dataStyles[$column]) || $dataStyles[$column] !== 'jsons') {
            return false;
        }

        if (isset($originAttributes[$column]) && $originAttributes[$column]) {
            $target = $originAttributes[$column] instanceof \Limepie\ArrayObject 
                ? $originAttributes[$column]->attributes 
                : $originAttributes[$column];

            if (is_string($attributes[$column])) {
                return json_decode($attributes[$column], true) == $target;
            }
            
            return $attributes[$column] == $target;
        }

        return false;
    }

    private static function buildUpdateColumn(string $column, string $columnBindName, array $attributes, array $dataStyles, array $plusAttributes, array $minusAttributes, array $rawAttributes, string $tableName): ?array
    {
        // 시간 컬럼 처리
        if ($column === 'created_ts') {
            return null; // created_ts는 수정하지 않음
        }
        
        if ($column === 'updated_ts') {
            if ($attributes[$column] ?? false) {
                return [
                    'column' => "`{$tableName}`.`{$column}` = :{$columnBindName}",
                    'binds' => [":{$columnBindName}" => $attributes[$column]]
                ];
            }
            return null;
        }

        // IP 컬럼 처리
        if ($column === 'ip') {
            return [
                'column' => "`{$tableName}`.`{$column}` = inet6_aton(:{$columnBindName})",
                'binds' => [":{$columnBindName}" => $attributes[$column] ?? \Limepie\getIp()]
            ];
        }

        // AES 처리
        if (self::isAesColumn($dataStyles, $column)) {
            return self::buildAesUpdate($column, $columnBindName, $dataStyles[$column], $attributes, $tableName);
        }

        // Point 처리
        if (self::isPointColumn($dataStyles, $column, $attributes)) {
            return self::buildPointUpdate($column, $columnBindName, $attributes[$column], $tableName);
        }

        // Plus/Minus 처리
        if (isset($plusAttributes[$column])) {
            return [
                'column' => "`{$tableName}`.`{$column}` = `{$tableName}`.`{$column}` + {$plusAttributes[$column]}",
                'binds' => []
            ];
        }

        if (isset($minusAttributes[$column])) {
            $name = "`{$tableName}`.`{$column}`";
            return [
                'column' => "`{$tableName}`.`{$column}` = IF({$name} > 0, {$name} - {$minusAttributes[$column]}, 0)",
                'binds' => []
            ];
        }

        // Raw 처리
        if (isset($rawAttributes[$column])) {
            $value = $attributes[$column];
            return [
                'column' => "`{$tableName}`.`{$column}` = " . str_replace('?', ":{$column}", $rawAttributes[$column]),
                'binds' => is_array($value) ? $value : []
            ];
        }

        // 일반 속성 처리
        if (array_key_exists($column, $attributes)) {
            $value = $attributes[$column];
            
            if (isset($dataStyles[$column])) {
                $value = DataProcessor::processForStorage($dataStyles[$column], $value);
            }

            return [
                'column' => "`{$tableName}`.`{$column}` = :{$columnBindName}",
                'binds' => [":{$columnBindName}" => $value]
            ];
        }

        return null;
    }

    private static function buildAesUpdate(string $column, string $columnBindName, string $dataStyle, array $attributes, string $tableName): array
    {
        $value = $attributes[$column] ?? null;
        
        $binds = [
            ":{$columnBindName}" => $dataStyle === 'aes_serialize' ? \serialize($value) : $value,
            ":{$columnBindName}_secretkey" => Aes::$salt
        ];

        return [
            'column' => "`{$tableName}`.`{$column}` = " . DataProcessor::buildAesEncryptSql($dataStyle, $columnBindName, "{$columnBindName}_secretkey"),
            'binds' => $binds
        ];
    }

    private static function buildPointUpdate(string $column, string $columnBindName, array $pointValue, string $tableName): array
    {
        if (is_null($pointValue)) {
            throw new Exception('empty point value');
        }

        return [
            'column' => "`{$tableName}`.`{$column}` = point(:{$columnBindName}1, :{$columnBindName}2)",
            'binds' => [
                ":{$columnBindName}1" => $pointValue[0],
                ":{$columnBindName}2" => $pointValue[1]
            ]
        ];
    }
}