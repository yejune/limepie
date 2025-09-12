<?php

declare(strict_types=1);

namespace Limepie\Model;

use Limepie\Exception;
use Limepie\Aes;
use Limepie\Model\Constants\QueryOperators;

/**
 * 조건문 생성 전담 클래스
 * - WHERE, AND, OR 조건 생성
 * - FULLTEXT 검색 처리
 * - BETWEEN, IN 조건 처리
 * - 복잡한 조건문 파싱
 */
class ConditionBuilder
{
    /**
     * 모델에서 바인딩 ID 가져오기
     */
    private static function getBindIdFromModel($model): string
    {
        // Model 객체의 nextBindId 속성에서 가져오기
        if ($model && isset($model->nextBindId) && $model->nextBindId !== null) {
            $bindId = $model->nextBindId;
            $model->nextBindId = null; // 사용 후 초기화
            return $bindId;
        }
        return uniqid();
    }
    
    /**
     * 간단한 조건문 생성 (Model.php에서 사용)
     */
    public static function buildSimpleCondition(string $column, string $operator, array $arguments, string $tableAliasName = '', $model = null): array
    {
        $sqlOperator = QueryOperators::toSqlOperator($operator);
        $condition = "";
        $binds = [];
        
        // 테이블 alias가 있으면 적용
        $columnWithAlias = $tableAliasName ? "`{$tableAliasName}`.`{$column}`" : $column;
        
        if (QueryOperators::requiresValue($operator)) {
            if (empty($arguments)) {
                throw new Exception("Operator {$operator} requires a value");
            }
            
            $value = $arguments[0];
            $bindKey = ':' . $column . '_' . self::getBindIdFromModel($model);
            
            if ($operator === QueryOperators::IN || $operator === QueryOperators::NOT_IN) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                $placeholders = [];
                foreach ($value as $i => $val) {
                    $key = $bindKey . '_' . $i;
                    $placeholders[] = $key;
                    $binds[$key] = $val;
                }
                $condition = "{$columnWithAlias} {$sqlOperator} (" . implode(', ', $placeholders) . ")";
            } else {
                $condition = "{$columnWithAlias} {$sqlOperator} {$bindKey}";
                $binds[$bindKey] = $value;
            }
        } else {
            // IS NULL, IS NOT NULL
            $condition = "{$columnWithAlias} {$sqlOperator}";
        }
        
        return [$condition, $binds];
    }
    /**
     * 키 이름을 분석하여 조건문 배열로 분리
     */
    public static function splitKey(string $name, int $offset = 0): array
    {
        $orgKey = substr($name, $offset);
        $whereKey = trim(\Limepie\decamelize($orgKey), '_ ');

        if (!$whereKey) {
            return [];
        }

        $matches = preg_split('#([^_]+])?(_and_|_or_)([^_]+])?#U', $whereKey, flags: PREG_SPLIT_OFFSET_CAPTURE);
        $splitKeys = [];
        $prevMatch = [];
        $offset = 0;

        foreach ($matches as $i => $match) {
            if ($prevMatch) {
                $operator = strtoupper(trim(str_replace($prevMatch[0], '', substr($whereKey, $offset, $match[1] - $offset)), '_'));
                $splitKeys[] = [
                    str_repeat('(', substr_count($prevMatch[0], '(_')), // open
                    trim($prevMatch[0], '()_'), // key
                    str_repeat(')', substr_count($prevMatch[0], '_)')), // close
                    $operator, // 기호
                ];
                $offset = $match[1];
            }
            $prevMatch = $match;
        }

        $operator = strtoupper(trim(str_replace($prevMatch[0], '', substr($whereKey, $offset, $match[1] - $offset)), '_'));
        $splitKeys[] = [
            str_repeat('(', substr_count($prevMatch[0], '(_')), // open
            trim($prevMatch[0], '()_'), // key
            str_repeat(')', substr_count($prevMatch[0], '_)')), // close
            $operator, // 기호
        ];

        return $splitKeys;
    }

    /**
     * 조건 연산자 반환
     */
    public static function getConditionOperator(string $key, mixed $value): ?string
    {
        if (strpos($key, 'gt_') === 0) return ' > ';
        if (strpos($key, 'lt_') === 0) return ' < ';
        if (strpos($key, 'ge_') === 0) return ' >= ';
        if (strpos($key, 'le_') === 0) return ' <= ';
        if (strpos($key, 'eq_') === 0) return ' = ';
        if (strpos($key, 'ne_') === 0) {
            return $value === null ? ' IS NOT NULL' : ' != ';
        }
        
        return null;
    }

    /**
     * 단일 조건문 생성
     */
    public static function buildSingleCondition(
        string $key,
        mixed $argument,
        string $bindKeyname,
        string $tableAliasName,
        array $allColumns,
        array $dataStyles,
        string $tableName
    ): array {
        $binds = [];
        $queryString = '';

        // FULLTEXT 검색 처리
        if (strpos($key, 'fulltext_boolean_') === 0) {
            $fixedKey = str_replace('_with_', "`, `{$tableAliasName}`.`", substr($key, 17));
            $queryString = "MATCH(`{$tableAliasName}`.`{$fixedKey}`) AGAINST (CONCAT(\"+\", :{$bindKeyname}, \"*\") IN BOOLEAN MODE)";
            $binds[":{$bindKeyname}"] = str_replace(' ', ' +', trim($argument));
            return [$queryString, $binds];
        }

        if (strpos($key, 'fulltext_') === 0) {
            $fixedKey = str_replace('_with_', "`, `{$tableAliasName}`.`", substr($key, 9));
            $queryString = "MATCH(`{$tableAliasName}`.`{$fixedKey}`) AGAINST (:{$bindKeyname} IN NATURAL LANGUAGE MODE)";
            $binds[":{$bindKeyname}"] = str_replace(' ', ' +', trim($argument));
            return [$queryString, $binds];
        }

        // WITH 조건 (다른 테이블 참조)
        if (strpos($key, '_with_') !== false) {
            $tmp = explode('_with_', $key);
            $fixedleftkey = $tmp[0];
            $conditionOperator = self::getConditionOperator($key, $argument);

            if ($conditionOperator) {
                $fixedleftkey = substr($tmp[0], 3);
            } else {
                $conditionOperator = ' = ';
            }

            $queryString = "`{$tableAliasName}`.`{$fixedleftkey}` {$conditionOperator} `{$argument->tableAliasName}`.`{$tmp[1]}`";
            return [$queryString, $binds];
        }

        // BETWEEN 조건
        if (strpos($key, 'between_') === 0) {
            $fixedKey = substr($key, 8);
            $queryString = "`{$tableAliasName}`.`{$fixedKey}` BETWEEN :{$bindKeyname}_a AND :{$bindKeyname}_b";
            $binds[":{$bindKeyname}_a"] = $argument[0];
            $binds[":{$bindKeyname}_b"] = $argument[1];
            return [$queryString, $binds];
        }

        // 배열 조건 (IN, NOT IN)
        if ($argument && is_array($argument)) {
            return self::buildArrayCondition($key, $argument, $bindKeyname, $tableAliasName, $allColumns);
        }

        // 일반 조건
        return self::buildRegularCondition($key, $argument, $bindKeyname, $tableAliasName, $dataStyles);
    }

    /**
     * 배열 조건문 생성 (IN, NOT IN)
     */
    private static function buildArrayCondition(
        string $key,
        array $argument,
        string $bindKeyname,
        string $tableAliasName,
        array $allColumns
    ): array {
        $bindkeys = [];
        $binds = [];

        if (strpos($key, 'ne_') === 0) {
            $fixedKey = substr($key, 3);
            foreach ($argument as $bindindex => $bindvalue) {
                $bindkey = ":{$bindKeyname}_{$bindindex}";
                $bindkeys[] = $bindkey;
                $binds[$bindkey] = $bindvalue;
            }
            $queryString = "`{$tableAliasName}`.`{$fixedKey}` NOT IN (" . implode(', ', $bindkeys) . ')';
        } else {
            if (!in_array($key, $allColumns, true)) {
                throw new Exception("Column '{$key}' not found in table");
            }

            foreach ($argument as $bindindex => $bindvalue) {
                $bindkey = ":{$bindKeyname}_{$bindindex}";
                $bindkeys[] = $bindkey;
                $binds[$bindkey] = $bindvalue;
            }
            $queryString = "`{$tableAliasName}`.`{$key}` IN (" . implode(', ', $bindkeys) . ')';
        }

        return [$queryString, $binds];
    }

    /**
     * 일반 조건문 생성
     */
    private static function buildRegularCondition(
        string $key,
        mixed $argument,
        string $bindKeyname,
        string $tableAliasName,
        array $dataStyles
    ): array {
        $binds = [];
        $fixedKey = substr($key, 3);
        $whereValue = $argument;

        // 객체 조건 처리
        if (is_object($whereValue)) {
            if (property_exists($whereValue, 'extraCondition')) {
                $leftCondition = sprintf(
                    $whereValue->extraCondition,
                    "`{$tableAliasName}`.`{$fixedKey}`"
                );

                if ($whereValue->bind !== null) {
                    $binds[":{$bindKeyname}"] = $whereValue->bind;
                }

                if (is_array($whereValue->extraBinds) && $whereValue->extraBinds) {
                    $binds = array_merge($binds, $whereValue->extraBinds);
                }

                return [$leftCondition, $binds];
            } else {
                throw new Exception("Invalid condition object for key '{$key}'");
            }
        }

        $leftCondition = "`{$tableAliasName}`.`{$fixedKey}`";

        // 연산자별 조건 생성
        if (strpos($key, 'gt_') === 0) {
            $queryString = "{$leftCondition} > :{$bindKeyname}";
        } elseif (strpos($key, 'lt_') === 0) {
            $queryString = "{$leftCondition} < :{$bindKeyname}";
        } elseif (strpos($key, 'ge_') === 0) {
            $queryString = "{$leftCondition} >= :{$bindKeyname}";
        } elseif (strpos($key, 'le_') === 0) {
            $queryString = "{$leftCondition} <= :{$bindKeyname}";
        } elseif (strpos($key, 'eq_') === 0) {
            $queryString = "{$leftCondition} = :{$bindKeyname}";
        } elseif (strpos($key, 'ne_') === 0) {
            if ($argument === null) {
                $queryString = "{$leftCondition} IS NOT NULL";
            } else {
                $queryString = "{$leftCondition} != :{$bindKeyname}";
            }
        } elseif (strpos($key, 'lb_') === 0) { // like binary
            $queryString = "{$leftCondition} like BINARY concat(\"%\", :{$bindKeyname}, \"%\")";
        } elseif (strpos($key, 'lk_') === 0) {
            $queryString = "{$leftCondition} like concat(\"%\", :{$bindKeyname}, \"%\")";
        } else {
            // 기본 등호 조건
            if ($argument === null) {
                $queryString = "{$leftCondition} IS NULL";
            } else {
                if ($fixedKey === 'ip') {
                    $queryString = "{$leftCondition} = inet6_aton(:{$bindKeyname})";
                } else {
                    // 데이터 스타일 처리
                    if (isset($dataStyles[$fixedKey])) {
                        if ($dataStyles[$fixedKey] === 'aes_hex') {
                            $binds[":{$bindKeyname}_secretkey"] = Aes::$salt;
                            unset($binds[":{$bindKeyname}"]);
                            $cleanBindKey = str_replace('aes_', '', $bindKeyname);
                            $binds[":{$cleanBindKey}"] = $whereValue;
                            $queryString = "{$leftCondition} = HEX(AES_ENCRYPT(:{$cleanBindKey}, :{$bindKeyname}_secretkey))";
                        } else {
                            $queryString = "{$leftCondition} = :{$bindKeyname}";
                        }
                    } else {
                        throw new Exception("Undefined column '{$fixedKey}'");
                    }
                }
            }
        }

        if ($argument !== null && !isset($binds[":{$bindKeyname}"])) {
            $binds[":{$bindKeyname}"] = $argument;
        }

        return [$queryString, $binds];
    }

    /**
     * 복수 조건문들을 생성
     */
    public static function getConditions(
        string $name,
        array $arguments,
        int $offset,
        string $tableAliasName,
        array $allColumns,
        array $dataStyles,
        string $tableName,
        int &$bindcount
    ): array {
        if (strpos($name, ' ') === false) {
            $splitKeys = self::splitKey($name, $offset);
            $binds = [];
            $conds = [];

            foreach ($splitKeys as $index => $splitKey) {
                ++$bindcount;
                [$open, $key, $close, $operator] = $splitKey;

                $bindKeyname = $tableAliasName . '_' . $key . '_' . $bindcount;

                if (!array_key_exists($index, $arguments)) {
                    // string 자체를 query로 사용
                    $queryString = $key;
                } else {
                    [$queryString, $conditionBinds] = self::buildSingleCondition(
                        $key,
                        $arguments[$index],
                        $bindKeyname,
                        $tableAliasName,
                        $allColumns,
                        $dataStyles,
                        $tableName
                    );
                    $binds = array_merge($binds, $conditionBinds);
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
                $conds[] = substr($name, $offset);
            } else {
                $conds[] = $name;
            }
        }

        return [$conds, $binds];
    }
}