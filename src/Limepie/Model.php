<?php

declare(strict_types=1);

namespace Limepie;

use Limepie\Pdo\Exception\OptimisticLock;
use Limepie\Model\QueryBuilder;
use Limepie\Model\DataProcessor;
use Limepie\Model\ConditionBuilder;
use Limepie\Model\RelationManager;
use Limepie\Model\SqlExecutor;
use Limepie\Model\Constants\QueryOperators;
use Doctrine\SqlFormatter\SqlFormatter;

use Limepie\Model\Base\Core;
use Limepie\Model\Traits\MagicMethods;
use Limepie\Model\Traits\CrudOperations;
use Limepie\Model\Traits\QueryMethods;

/**
 * Limepie ORM Model 클래스 - PHP 객체-관계 매핑 클래스
 * 
 * 주요 기능:
 * - 매직 메서드를 통한 동적 쿼리 생성 (condition*, and*, or*, orderBy* 등)
 * - 관계형 데이터 처리 (relation/relations 체이닝)  
 * - SQL 쿼리 빌더 및 파라미터 바인딩
 * - 데이터 암호화, 직렬화 처리
 * - JOIN, 서브쿼리, 집계함수 지원
 * - ArrayObject 상속으로 배열/객체 호환 인터페이스
 * 
 * 사용 예제:
 * ```php
 * class User extends Model {
 *     public $tableName = 'users';
 * }
 * 
 * $user = (new User())($pdo);
 * 
 * // 기본 쿼리 - OPERATOR + COLUMN 패턴
 * $result = $user->conditionEqName('John')->andGtAge(25)->get();
 * 
 * // 복잡한 Relations 체이닝
 * $posts = $user->relations(
 *     (new Post())($pdo)->matchSeqWithUserSeq()
 *              ->relations((new Comment())($pdo))
 * )->get();
 * 
 * // 집계 쿼리
 * $count = $user->conditionEqIsActive(1)->getCount();
 * $stats = $user->conditionGeCreatedTs('2024-01-01')->sumPoints()->getSum();
 * ```
 * 
 * 구조:
 * - Core: 84개 속성과 핵심 기능
 * - MagicMethods: 동적 메서드 처리 
 * - CrudOperations: CREATE/UPDATE/DELETE SQL 생성
 * - QueryMethods: ORDER BY, LIMIT, GROUP BY 등
 * 
 * @author Limepie Team
 * @version 2.0
 * @package Limepie\Model
 */
class Model extends Core
{
    use MagicMethods, CrudOperations, QueryMethods;

    /**
     * 다음에 호출될 조건에 사용할 바인딩 ID (테스트용)
     */
    public $nextBindId = null;

    // === 핵심 조건 빌더 메서드들 ===
    
    /**
     * 다음 조건에 사용할 바인딩 ID 지정 (테스트용)
     */
    public function appendBindId(?string $bindId = null): self
    {
        $this->nextBindId = $bindId;
        return $this;
    }
    
    /**
     * WHERE 조건 생성 - 매직 메서드의 핵심 로직
     * 
     * 지원하는 패턴:
     * - conditionEqName('John') -> name = 'John'
     * - conditionGtAge(25) -> age > 25  
     * - conditionLikeEmail('%@gmail.com') -> email LIKE '%@gmail.com'
     * - conditionInId([1,2,3]) -> id IN (1, 2, 3)
     * - conditionIsNotNullStatus() -> status IS NOT NULL
     * 
     * @param string $name 메서드명 (예: conditionEqName)
     * @param array $arguments 인자들 
     * @param int $offset 'condition' 부분을 제외한 시작 위치
     * @return self 메서드 체이닝 지원
     */
    protected function buildCondition($name, $arguments, $offset)
    {
        $methodPart = substr($name, $offset);
        
        // 괄호 처리 (인자가 없을 때만)
        if (in_array($methodPart, ['(', ')'], true) && !isset($arguments[0])) {
            $this->condition .= $methodPart;
            return $this;
        }
        
        // 일반적인 컬럼 조건 처리
        $operator = QueryOperators::EQUAL;
        $column = $methodPart;
        
        // 연산자 목록으로 직접 확인 (conditionEqName -> Eq + Name)
        $operators = QueryOperators::getAllOperators();
        
        foreach ($operators as $op) {
            if (strpos($methodPart, $op) === 0) {
                $operator = $op;
                $column = substr($methodPart, strlen($op));
                break;
            }
        }
        
        // CamelCase를 snake_case로 변환 (Name -> name)
        $column = $this->camelToSnake($column);
        
        // 실제 SQL 조건과 바인딩 파라미터 생성 (테이블 alias 포함)
        [$condition, $binds] = ConditionBuilder::buildSimpleCondition($column, $operator, $arguments, $this->tableAliasName, $this);
        
        // condition* 메서드들은 AND를 자동으로 추가하지 않음 (명시적으로 and* 메서드를 사용해야 함)
        $this->condition .= $condition;
        $this->binds = array_merge($this->binds, $binds);
        
        return $this;
    }

    /**
     * AND 조건 생성 - 기존 조건에 AND로 추가 연결
     * 
     * 사용 예제:
     * - andEqName('John') -> AND name = 'John'  
     * - andGtAge(25) -> AND age > 25
     * - andInStatus(['active', 'pending']) -> AND status IN ('active', 'pending')
     * 
     * @param string $name 메서드명 (예: andEqName)
     * @param array $arguments 인자들
     * @param int $offset 'and' 부분을 제외한 시작 위치
     * @return self 메서드 체이닝 지원
     */
    protected function buildAnd($name, $arguments, $offset)
    {
        $methodPart = substr($name, $offset);
        
        // 괄호 처리
        if (in_array($methodPart, ['(', ')'], true)) {
            $this->condition .= ' AND ' . $methodPart;
            return $this;
        }
        
        // Raw SQL 처리 (공백이 포함된 경우)
        if (strpos($methodPart, ' ') !== false) {
            $this->condition .= ' AND ' . $methodPart;
            
            if (isset($arguments[0])) {
                $this->binds = array_merge($this->binds, $arguments[0]);
            }
            
            return $this;
        }
        
        // 일반적인 컬럼 조건 처리 (기존 로직)
        $operator = QueryOperators::EQUAL;
        $column = $methodPart;
        
        // 연산자 목록으로 직접 확인 (andGtAge -> Gt + Age)
        $operators = QueryOperators::getAllOperators();
        
        foreach ($operators as $op) {
            if (strpos($methodPart, $op) === 0) {
                $operator = $op;
                $column = substr($methodPart, strlen($op));
                break;
            }
        }
        
        $column = $this->camelToSnake($column);
        [$condition, $binds] = ConditionBuilder::buildSimpleCondition($column, $operator, $arguments, $this->tableAliasName, $this);
        
        // 항상 AND로 연결 (기존 조건이 없으면 첫 조건으로)
        $this->condition .= ($this->condition ? ' AND ' : '') . $condition;
        $this->binds = array_merge($this->binds, $binds);
        
        return $this;
    }

    protected function buildSum($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        $column = $this->camelToSnake($column);
        $this->sumColumn = $column;
        return $this;
    }

    protected function buildAvg($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        $column = $this->camelToSnake($column);
        $this->avgColumn = $column;
        return $this;
    }

    protected function buildOr($name, $arguments)
    {
        $offset = 2; // 'or' 문자열 길이
        $operator = substr($name, $offset);
        
        if (in_array($operator, [')', '('], true)) {
            // 괄호 처리
            $this->condition .= ' OR ' . $operator;
        } elseif (strpos($operator, ' ') !== false) {
            // 공백이 포함된 직접 SQL 조건
            $this->condition .= ' OR ' . $operator;
            if (isset($arguments[0])) {
                $this->binds = array_merge($this->binds, $arguments[0]);
            }
        } else {
            // 일반적인 OR 조건 처리
            $operatorType = QueryOperators::EQUAL;
            $column = $operator;
            
            // 연산자 목록으로 직접 확인
            $operators = QueryOperators::getAllOperators();
            
            foreach ($operators as $op) {
                if (strpos($operator, $op) === 0) {
                    $operatorType = $op;
                    $column = substr($operator, strlen($op));
                    break;
                }
            }
            
            $column = $this->camelToSnake($column);
            
            [$condition, $binds] = ConditionBuilder::buildSimpleCondition($column, $operatorType, $arguments, $this->tableAliasName);
            
            if ($condition) {
                $this->condition .= ' OR ' . $condition;
            }
            if ($binds) {
                $this->binds = array_merge($this->binds, $binds);
            }
        }
        
        return $this;
    }

    // === 관계 메서드들 ===
    
    protected function buildMatch($name, $arguments)
    {
        // Match 키 설정 로직
        if (preg_match('/match(.+?)With(.+?)$/', $name, $matches)) {
            $this->leftKeyName = $this->camelToSnake($matches[1]);
            $this->rightKeyName = $this->camelToSnake($matches[2]);
            
            if (isset($arguments[0])) {
                $this->matchKeyRemove = (bool)$arguments[0];
            }
        }
        return $this;
    }

    protected function buildRelation($name, $arguments, $isMany)
    {
        if (isset($arguments[0])) {
            if ($isMany) {
                $this->oneToMany[] = $arguments[0];
            } else {
                $this->oneToOne[] = $arguments[0];
            }
        }
        return $this;
    }

    protected function buildJoin($name, $arguments)
    {
        if (isset($arguments[0])) {
            $joinType = strpos($name, 'leftJoin') === 0 ? 'LEFT' : 'INNER';
            $this->joinModels[] = [
                'model' => $arguments[0],
                'type' => $joinType
            ];
        }
        return $this;
    }

    protected function buildJoinOn($name, $arguments)
    {
        $offset = 2; // 'on' 문자열 길이
        $operator = substr($name, $offset);
        
        if (in_array($operator, [')', '('], true) && !isset($arguments[0])) {
            // 괄호만 추가
            $this->onCondition .= $operator;
        } else {
            // 일반적인 ON 조건 처리
            $column = $this->camelToSnake($operator);
            $operatorType = QueryOperators::EQUAL;
            
            // 연산자 추출
            $operatorPattern = '/(.+?)' . QueryOperators::getOperatorPattern() . '$/';
            if (preg_match($operatorPattern, $operator, $matches)) {
                $column = $this->camelToSnake($matches[1]);
                $operatorType = $matches[2];
            }
            
            [$condition, $binds] = ConditionBuilder::buildSimpleCondition($column, $operatorType, $arguments, $this->tableAliasName);
            
            $this->onCondition .= ($this->onCondition ? ' AND ' : '') . $condition;
            $this->onConditionBinds = array_merge($this->onConditionBinds, $binds);
        }
        
        return $this;
    }

    protected function buildPossible($name, $arguments)
    {
        $tmp = substr($name, 8); // 'possible' 문자열 길이
        $this->possibleRelationKey = $this->camelToSnake($tmp);
        
        if (isset($arguments[0])) {
            $this->possibleRelationValue = $arguments[0];
        }
        
        return $this;
    }

    // === 데이터 조회 메서드들 ===
    
    protected function buildGetBy($name, $arguments, $offset)
    {
        $this->buildCondition('condition' . substr($name, $offset), $arguments, 9);
        return $this->get();
    }

    protected function buildGetsBy($name, $arguments, $offset)
    {
        $this->buildCondition('condition' . substr($name, $offset), $arguments, 9);
        return $this->gets();
    }

    protected function buildCount($name, $arguments, $offset, $isGets = false)
    {
        $this->buildCondition('condition' . substr($name, $offset), $arguments, 9);
        return $this->getCount();
    }

    protected function buildGetSum($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        $this->buildSum('sum' . $column, [], 3);
        return $this->getSum();
    }

    protected function buildGetAvg($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        $this->buildAvg('avg' . $column, [], 3);
        return $this->getAvg();
    }

    // === 컬럼 관리 메서드들 ===
    
    protected function buildAddColumn($name, $arguments)
    {
        $column = substr($name, 9); // 'addColumn' 이후
        $column = $this->camelToSnake($column);
        $this->addColumns[$column] = $arguments[0] ?? true;
        return $this;
    }

    protected function buildRemoveColumn($name, $arguments)
    {
        $column = substr($name, 12); // 'removeColumn' 이후
        $column = $this->camelToSnake($column);
        $this->removeColumns[] = $column;
        return $this;
    }

    // === 속성 설정 메서드들 ===
    
    protected function buildSet($name, $arguments)
    {
        $column = substr($name, 3); // 'set' 이후
        $column = $this->camelToSnake($column);
        
        if (isset($arguments[0])) {
            $this->attributes[$column] = $arguments[0];
        }
        
        return $this;
    }

    protected function buildSetRaw($name, $arguments)
    {
        $column = substr($name, 6); // 'setRaw' 이후
        $column = $this->camelToSnake($column);
        
        if (isset($arguments[0])) {
            $this->rawAttributes[$column] = $arguments[0];
        }
        
        return $this;
    }

    protected function buildNew($name, $arguments)
    {
        // 새 인스턴스에 값 설정
        return $this->buildSet($name, $arguments);
    }

    protected function buildNewRaw($name, $arguments)
    {
        // 새 인스턴스에 Raw 값 설정
        return $this->buildSetRaw('setRaw' . substr($name, 6), $arguments);
    }

    protected function buildPlus($name, $arguments)
    {
        $column = substr($name, 4); // 'plus' 이후
        $column = $this->camelToSnake($column);
        
        if (isset($arguments[0])) {
            $this->plusAttributes[$column] = $arguments[0];
        }
        
        return $this;
    }

    protected function buildMinus($name, $arguments)
    {
        $column = substr($name, 5); // 'minus' 이후
        $column = $this->camelToSnake($column);
        
        if (isset($arguments[0])) {
            $this->minusAttributes[$column] = $arguments[0];
        }
        
        return $this;
    }

    protected function buildGetColumn($name, $arguments)
    {
        $column = substr($name, 3); // 'get' 이후
        $column = $this->camelToSnake($column);
        
        return $this->attributes[$column] ?? null;
    }

    protected function buildAddRawColumn($name, $arguments)
    {
        $column = substr($name, 12); // 'addRawColumn' 이후
        $column = $this->camelToSnake($column);
        
        if (isset($arguments[0])) {
            $this->rawColumnAliasNames[$column] = $arguments[0];
        }
        
        return $this;
    }

    // === 기본 데이터 조회 메서드들 ===

    protected function addAllColumns()
    {
        $this->isRemoveAllColumn = false;
        $this->selectColumns = $this->allColumns;
    }

    protected function get(...$arguments)
    {
        // 단일 레코드 조회 구현
        $sql = $this->buildSelectQuery();
        return $this->executeGet($sql, $this->binds);
    }

    public function get1(null|array|string $sql = null, array $binds = []): ?self
    {
        throw new Exception('not support get1');
    }

    protected function gets(...$arguments)
    {
        // 다중 레코드 조회 구현  
        $sql = $this->buildSelectQuery();
        return $this->executeGets($sql, $this->binds);
    }

    public function getCount()
    {
        // 개수 조회 구현
        $sql = $this->buildCountQuery();
        return $this->executeGetCount($sql, $this->binds);
    }

    public function getSum()
    {
        // 합계 조회 구현
        $sql = $this->buildSumQuery();
        return $this->executeGetSum($sql, $this->binds);
    }

    public function getAvg()
    {
        // 평균 조회 구현
        $sql = $this->buildAvgQuery();
        return $this->executeGetAvg($sql, $this->binds);
    }

    // === 쿼리 빌더 헬퍼 메서드들 ===

    private function buildSelectQuery(): string
    {
        return SqlExecutor::buildSelectQuery(
            $this->tableName,
            $this->tableAliasName,
            $this->selectColumns,
            $this->condition,
            $this->and,
            $this->orderBy,
            $this->limit,
            $this->offset,
            $this->joinModels,
            $this->forceIndexes
        );
    }

    private function buildCountQuery(): string
    {
        return SqlExecutor::buildCountQuery(
            $this->tableName,
            $this->condition,
            $this->and,
            $this->joinModels
        );
    }

    private function buildSumQuery(): string
    {
        return SqlExecutor::buildSumQuery(
            $this->tableName,
            $this->sumColumn,
            $this->condition,
            $this->and,
            $this->joinModels
        );
    }

    private function buildAvgQuery(): string
    {
        return SqlExecutor::buildAvgQuery(
            $this->tableName,
            $this->avgColumn,
            $this->condition,
            $this->and,
            $this->joinModels
        );
    }

    // === SQL 실행 메서드들 ===

    private function executeGet(string $sql, array $binds)
    {
        return SqlExecutor::executeGet($this->getConnect(), $sql, $binds);
    }

    private function executeGets(string $sql, array $binds)
    {
        return SqlExecutor::executeGets($this->getConnect(), $sql, $binds);
    }

    private function executeGetCount(string $sql, array $binds): int
    {
        return SqlExecutor::executeGetCount($this->getConnect(), $sql, $binds);
    }

    private function executeGetSum(string $sql, array $binds): float
    {
        return SqlExecutor::executeGetSum($this->getConnect(), $sql, $binds);
    }

    private function executeGetAvg(string $sql, array $binds): float
    {
        return SqlExecutor::executeGetAvg($this->getConnect(), $sql, $binds);
    }

    private function camelToSnake($input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    public function getConditionAndBinds(string $whereKey, array $arguments = [], int $offset = 0): array
    {
        $condition = '';
        $binds = [];
        $conds = [];
        [$conds, $binds] = ConditionBuilder::getConditions($whereKey, $arguments, $offset, $this->tableName, $this->tableAliasName, $this->allColumns, $this->dataStyles);
        $condition = \trim(\implode(PHP_EOL . '        ', $conds));
        return [$condition, $binds];
    }

    public function condition(string $string): self
    {
        $this->condition .= ' ' . $string;
        return $this;
    }

    public function and(?string $key = null, $value = null): self
    {
        if (null === $key) {
            $this->condition .= ' AND ';
        } else {
            return $this->buildAnd('and' . ucfirst($key), [$value], 0);
        }
        return $this;
    }

    public function or(?string $key = null, $value = null): self
    {
        if (null === $key) {
            $this->condition .= ' OR ';
        } else {
            return $this->buildOr('or' . ucfirst($key), [$value]);
        }
        return $this;
    }

    public function where(string $key, $value = null): self
    {
        return $this->buildCondition('where' . ucfirst($key), [$value], 0);
    }

    public function match(string $key, $value = null): self
    {
        return $this->buildMatch('match' . ucfirst($key), [$value]);
    }

    public function relation($model): self
    {
        return $this->buildRelation('relation', [$model], false);
    }

    public function relations($model): self
    {
        return $this->buildRelation('relations', [$model], true);
    }

    public function oneToOne($model): self
    {
        $this->oneToOne[] = $model;
        return $this;
    }

    public function oneToMany($model): self
    {
        $this->oneToMany[] = $model;
        return $this;
    }

    public function on(string $key, $value = null): self
    {
        return $this->buildJoinOn('on' . ucfirst($key), [$value]);
    }

    public function open(): self
    {
        $this->condition .= ' (';
        return $this;
    }

    public function openParenthesis(): self
    {
        return $this->open();
    }

    public function closeParenthesis(): self
    {
        $this->condition .= ') ';
        return $this;
    }

    public function getQuery()
    {
        return [$this->query, $this->binds];
    }

    /**
     * 실제 실행 없이 SQL만 생성 (모든 CRUD 및 집계 작업 지원)
     * 
     * 지원 작업:
     * - SELECT: get, gets, get1, getBy, getsBy, get1By
     * - CREATE/INSERT: create
     * - UPDATE: update
     * - DELETE: delete
     * - SUM: getSum, getSumBy
     * - COUNT: getCount, getCountBy
     * - AVG: getAvg, getAvgBy
     * - MIN: getMin, getMinBy
     * - MAX: getMax, getMaxBy
     */
    public function getSql(string $operation = 'SELECT', ?string $aggregateColumn = null): array
    {
        switch (strtoupper($operation)) {
            case 'CREATE':
            case 'INSERT':
                return $this->buildCreate();
                
            case 'UPDATE':
                [$sql, $binds] = $this->buildUpdate();
                if (!empty($this->condition)) {
                    $sql .= " WHERE " . $this->condition;
                    $binds = array_merge($binds, $this->binds);
                }
                return [$sql, $binds];
                
            case 'DELETE':
                $sql = "DELETE FROM `{$this->tableName}`";
                $binds = [];
                
                // 조건이 있으면 우선 사용
                if (!empty($this->condition)) {
                    $sql .= " WHERE " . $this->condition;
                    $binds = $this->binds ?: [];
                } 
                // 조건이 없고 primaryKey 값이 있으면 해당 레코드 삭제
                else if (isset($this->attributes[$this->primaryKeyName])) {
                    $primaryValue = $this->attributes[$this->primaryKeyName];
                    $bindKey = ':' . $this->primaryKeyName;
                    $sql .= " WHERE `{$this->tableAliasName}`.`{$this->primaryKeyName}` = {$bindKey}";
                    $binds = [$bindKey => $primaryValue];
                }
                // seq 속성이 직접 설정된 경우도 처리
                else if (isset($this->{$this->primaryKeyName})) {
                    $primaryValue = $this->{$this->primaryKeyName};
                    $bindKey = ':' . $this->primaryKeyName;
                    $sql .= " WHERE `{$this->tableAliasName}`.`{$this->primaryKeyName}` = {$bindKey}";
                    $binds = [$bindKey => $primaryValue];
                }
                
                return [$sql, $binds];
                
            case 'SUM':
                $column = $aggregateColumn ?: $this->sumColumn ?: $this->primaryKeyName;
                $sql = $this->buildAggregateQuery('SUM', $column);
                return [$sql, $this->binds ?: []];
                
            case 'COUNT':
                $column = $aggregateColumn ?: '*';
                $sql = $this->buildAggregateQuery('COUNT', $column);
                return [$sql, $this->binds ?: []];
                
            case 'AVG':
                $column = $aggregateColumn ?: $this->avgColumn ?: $this->primaryKeyName;
                $sql = $this->buildAggregateQuery('AVG', $column);
                return [$sql, $this->binds ?: []];
                
            case 'MIN':
                $column = $aggregateColumn ?: $this->primaryKeyName;
                $sql = $this->buildAggregateQuery('MIN', $column);
                return [$sql, $this->binds ?: []];
                
            case 'MAX':
                $column = $aggregateColumn ?: $this->primaryKeyName;
                $sql = $this->buildAggregateQuery('MAX', $column);
                return [$sql, $this->binds ?: []];
                
            case 'SELECT':
            default:
                // buildSelectQuery 메서드 활용
                $sql = $this->buildSelectQuery();
                return [$sql, $this->binds ?: []];
        }
    }

    /**
     * 집계 함수 쿼리 생성
     */
    private function buildAggregateQuery(string $function, string $column): string
    {
        $sql = "SELECT {$function}(";
        
        if ($column === '*') {
            $sql .= '*';
        } else {
            $sql .= "`{$this->tableAliasName}`.`{$column}`";
        }
        
        $sql .= ") FROM `{$this->tableName}` AS `{$this->tableAliasName}`";
        
        // WHERE 조건 추가
        if (!empty($this->condition)) {
            $sql .= " WHERE " . $this->condition;
        }
        
        // GROUP BY 추가
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . $this->groupBy;
        }
        
        return $sql;
    }

    public function getQueryBinds(array $binds = [])
    {
        $result = [];
        foreach ($binds as $key => $value) {
            if (0 === \strpos($key, ':aes_') || false !== \strpos($key, '_aes_')) {
                $result[$key] = \Limepie\decrypt($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function getLimit()
    {
        if ($this->limit) {
            if ($this->offset) {
                return "LIMIT {$this->offset}, {$this->limit}";
            } else {
                return "LIMIT {$this->limit}";
            }
        }
        return '';
    }

    public function getOrderBy()
    {
        return $this->orderBy ? "ORDER BY {$this->orderBy}" : '';
    }

    public function getGroupBy()
    {
        return $this->groupBy ? "GROUP BY {$this->groupBy}" : '';
    }

    public function getGroupLimit()
    {
        return $this->groupLimit;
    }

    public function replaceQueryBinds(string $query, array $binds): string
    {
        foreach ($binds as $key => $value) {
            $query = str_replace($key, "'" . addslashes($value) . "'", $query);
        }
        return $query;
    }
}