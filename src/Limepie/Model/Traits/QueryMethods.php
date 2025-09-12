<?php

declare(strict_types=1);

namespace Limepie\Model\Traits;

/**
 * Model 쿼리 메서드들 - 조건, 정렬, 제한 등
 */
trait QueryMethods
{
    public function limit($offset, $limit = null)
    {
        if ($limit === null) {
            $this->limit = $offset;
            $this->offset = null;
        } else {
            $this->offset = $offset;
            $this->limit = $limit;
        }
        return $this;
    }

    public function groupLimit($limit)
    {
        $this->groupLimit = $limit;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = $column . ' ' . strtoupper($direction);
        return $this;
    }

    public function groupBy($column)
    {
        $this->groupBy = $column;
        return $this;
    }

    public function forceIndex($index)
    {
        $this->forceIndexes[] = $index;
        return $this;
    }

    public function keyName($keyName)
    {
        $this->keyName = $keyName;
        return $this;
    }

    public function valueName($valueName)
    {
        $this->valueName = $valueName;
        return $this;
    }

    public function selectColumns(array $columns)
    {
        $this->selectColumns = $columns;
        return $this;
    }

    public function addSelectColumn($column)
    {
        if (!in_array($column, $this->selectColumns)) {
            $this->selectColumns[] = $column;
        }
        return $this;
    }

    public function removeSelectColumn($column)
    {
        $this->selectColumns = array_filter($this->selectColumns, function($col) use ($column) {
            return $col !== $column;
        });
        return $this;
    }

    protected function buildGroupBy($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        $column = $this->camelToSnake($column);
        $this->groupBy = $column;
        return $this;
    }

    protected function buildOrderBy($name, $arguments, $offset)
    {
        $column = substr($name, $offset);
        
        // 컬럼명에서 방향(Asc/Desc) 추출
        $direction = 'ASC';
        if (preg_match('/(.+)(Asc|Desc)$/', $column, $matches)) {
            $column = $matches[1];
            $direction = strtoupper($matches[2]);
        }
        
        $column = $this->camelToSnake($column);
        
        // 인자로 방향이 전달된 경우 우선 사용
        if (isset($arguments[0])) {
            $direction = strtoupper($arguments[0]);
        }
        
        $this->orderBy = $column . ' ' . $direction;
        return $this;
    }

    protected function buildForceIndex($name, $arguments, $offset)
    {
        $index = substr($name, $offset);
        $index = $this->camelToSnake($index);
        $this->forceIndexes[] = $index;
        return $this;
    }

    protected function buildKeyName($name, $arguments)
    {
        $keyName = substr($name, 7); // 'keyName' 이후
        $keyName = $this->camelToSnake($keyName);
        $this->keyName = $keyName;
        return $this;
    }

    protected function buildAlias($name, $arguments)
    {
        $alias = substr($name, 5); // 'alias' 이후
        $this->tableAliasName = strtolower($alias);
        return $this;
    }


    private function camelToSnake($input)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    public function fetchKey(callable $callback): self
    {
        $this->keyName = $callback;
        return $this;
    }

    public function fetchValue(callable $callback): self
    {
        $this->valueName = $callback;
        return $this;
    }


    public function column(string $columnName): self
    {
        $this->selectColumns = [$columnName];
        return $this;
    }

    public function filter(callable $callback): self
    {
        // 필터 콜백 저장
        $this->filterCallback = $callback;
        return $this;
    }

    public function duplication($flag = true): self
    {
        $this->duplication = $flag;
        return $this;
    }

    public function parentNode($flag = true): self
    {
        $this->parentNode = $flag;
        return $this;
    }

    public function addColumn(string $columnName, null|\Closure|string $aliasName = null, ?string $format = null)
    {
        if (null === $aliasName) {
            $this->selectColumns[] = $columnName;
        } elseif (\is_callable($aliasName)) {
            $this->addColumns[$columnName] = $aliasName;
        } else {
            $this->selectColumns[] = $columnName . ' AS ' . $aliasName;
        }
        return $this;
    }

    public function addColumns(array $columns): self
    {
        $this->selectColumns = \array_merge($this->selectColumns, $columns);
        return $this;
    }

    public function removeColumn(string $columnName): self
    {
        $this->removeColumns[] = $columnName;
        return $this;
    }

    public function removeColumns(array $columns): self
    {
        $this->removeColumns = array_merge($this->removeColumns, $columns);
        return $this;
    }

    public function removeAllColumns(): self
    {
        $this->isRemoveAllColumn = true;
        $this->selectColumns = [];
        return $this;
    }

    public function onlyColumns(array $columns): self
    {
        $this->selectColumns = $columns;
        return $this;
    }

    public function addColumnIsDisplayCondition(): self
    {
        $this->addRawColumnIsDisplayCondition('
            (
                -- 디스플레이 상태 조건
                (
                    `' . $this->tableAliasName . '`.`is_display` = 1
                    OR (
                        `' . $this->tableAliasName . '`.`is_display` = 2
                        AND `' . $this->tableAliasName . '`.`display_start_dt` < NOW()
                        AND `' . $this->tableAliasName . '`.`display_end_dt` > NOW()
                    )
                    OR (
                        `' . $this->tableAliasName . '`.`is_display` = 3
                        AND `' . $this->tableAliasName . '`.`display_start_dt` < NOW()
                    )
                )
                AND (
                    -- 전체 표시이거나 요일별 표시 조건
                    `' . $this->tableAliasName . '`.`is_display` = 1
                    OR (
                        `' . $this->tableAliasName . '`.`is_allday` = 0
                        OR (
                            `' . $this->tableAliasName . '`.`is_allday` = 1
                            AND (
                                -- 요일별 표시 조건
                                (
                                    `' . $this->tableAliasName . '`.`is_sunday` = 1
                                    AND DAYOFWEEK(NOW()) = 1
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_monday` = 1
                                    AND DAYOFWEEK(NOW()) = 2
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_tuesday` = 1
                                    AND DAYOFWEEK(NOW()) = 3
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_wednesday` = 1
                                    AND DAYOFWEEK(NOW()) = 4
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_thursday` = 1
                                    AND DAYOFWEEK(NOW()) = 5
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_friday` = 1
                                    AND DAYOFWEEK(NOW()) = 6
                                )
                                OR (
                                    `' . $this->tableAliasName . '`.`is_saturday` = 1
                                    AND DAYOFWEEK(NOW()) = 7
                                )
                            )
                        )
                    )
                )
            )
        ');

        return $this;
    }

    public function addColumnIsStartEndDtRange(): self
    {
        $this->addRawColumnIsStartEndDtRange('
            (
                `' . $this->tableAliasName . '`.`start_dt` < NOW()
                AND `' . $this->tableAliasName . '`.`end_dt` > NOW()
            )
        ');

        return $this;
    }

    public function conditionDisplayCondition(): self
    {
        $this
            ->condition('(')
            ->conditionIsDisplay(1)
            ->or('(')
            ->conditionIsDisplay(2)
            ->andLtDisplayStartDt(\date('Y-m-d H:i:s'))
            ->andGtDisplayEndDt(\date('Y-m-d H:i:s'))
            ->condition(')')
            ->or('(')
            ->conditionIsDisplay(3)
            ->andLtDisplayStartDt(\date('Y-m-d H:i:s'))
            ->condition(')')
            ->condition(')')
            ->and('(')
            ->conditionIsDisplay(1)
            ->or('(')
            ->conditionIsAllday(0)
            ->or('(')
            ->conditionIsAllday(1)
            ->and('(')
            ->condition('(')
            ->conditionIsSunday(1)->and('DAYOFWEEK(NOW()) = 1')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsMonday(1)->and('DAYOFWEEK(NOW()) = 2')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsTuesday(1)->and('DAYOFWEEK(NOW()) = 3')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsWednesday(1)->and('DAYOFWEEK(NOW()) = 4')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsThursday(1)->and('DAYOFWEEK(NOW()) = 5')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsFriday(1)->and('DAYOFWEEK(NOW()) = 6')
            ->condition(')')
            ->or()
            ->condition('(')
            ->conditionIsSaturday(1)->and('DAYOFWEEK(NOW()) = 7')
            ->condition(')')
            ->condition(')')
            ->condition(')')
            ->condition(')')
            ->condition(')')
        ;

        return $this;
    }

    public function andDisplayCondition(): self
    {
        $this->and()->conditionDisplayCondition();

        return $this;
    }

    public function conditionStartEndDtRange($currentTime = null): self
    {
        $time = $currentTime ?? \date('Y-m-d H:i:s');
        return $this->condition('(')
            ->conditionLtStartDt($time)
            ->andGtEndDt($time)
            ->condition(')');
    }

    public function andStartEndDtRange($currentTime = null): self
    {
        $this->and();
        return $this->conditionStartEndDtRange($currentTime);
    }

    public function onStartEndDtRange($currentTime = null): self
    {
        $sql = '(`' . $this->tableAliasName . '`.`start_dt` < NOW() AND `' . $this->tableAliasName . '`.`end_dt` > NOW())';

        if ($this->onCondition) {
            $this->onCondition .= ' AND ' . $sql;
        } else {
            $this->onCondition = $sql;
        }

        return $this;
    }

    public function addRawColumnIsDisplayCondition(string $rawSql): self
    {
        $this->rawColumnAliasNames['is_display_condition'] = $rawSql;
        return $this;
    }

    public function addRawColumnIsStartEndDtRange(string $rawSql): self  
    {
        $this->rawColumnAliasNames['is_start_end_dt_range'] = $rawSql;
        return $this;
    }
}