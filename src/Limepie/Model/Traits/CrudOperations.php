<?php

declare(strict_types=1);

namespace Limepie\Model\Traits;

use Limepie\Model\QueryBuilder;

/**
 * Model CRUD 작업 처리
 */
trait CrudOperations
{
    /**
     * MySQL 풀텍스트 검색에서 사용할 수 있도록 검색어를 안전하게 만드는 함수
     */
    public static function safeFulltextKeyword($searchTerm)
    {
        $searchTerm = \trim($searchTerm);
        if (empty($searchTerm)) {
            return '';
        }

        $operators  = ['+', '-', '<', '>', '(', ')', '~', '*', '"', '@', '>', '<'];
        $searchTerm = \str_replace($operators, ' ', $searchTerm);
        $searchTerm = \preg_replace('/\s+/', ' ', $searchTerm);
        $words     = \explode(' ', $searchTerm);
        $safeWords = [];

        foreach ($words as $word) {
            $word = \trim($word);
            if (empty($word)) {
                continue;
            }
            if (\mb_strlen($word) >= 3) {
                $word = \preg_replace('/[^\p{L}\p{N}]/u', ' ', $word);
                if (!empty($word)) {
                    $safeWords[] = $word;
                }
            }
        }

        if (!empty($safeWords)) {
            return \implode(' ', $safeWords);
        }
        return '';
    }

    public function replace() 
    {
        // Replace 작업 구현
    }

    public function buildCreate($prefix = '')
    {
        [$columns, $binds, $values] = QueryBuilder::buildCreateComponents(
            $this->allColumns,
            $this->attributes,
            $this->dataStyles,
            $this->sequenceName,
            $this->tableName,
            $prefix,
            $this->rawAttributes
        );
        
        $sql = "INSERT INTO `{$this->tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        
        return [$sql, $binds];
    }

    public function buildUpdate($prefix = '')
    {
        [$columns, $binds, $sames] = QueryBuilder::buildUpdateComponents(
            $this->allColumns,
            $this->attributes,
            $this->originAttributes,
            $this->dataStyles,
            $this->plusAttributes,
            $this->minusAttributes,
            $this->rawAttributes,
            $this->sequenceName,
            $this->tableName,
            $prefix
        );
        
        if (empty($columns)) {
            return [null, []];
        }
        
        $sql = "UPDATE `{$this->tableName}` SET " . implode(', ', $columns);
        
        return [$sql, $binds];
    }

    public function create()
    {
        if (!$this->getConnect()) {
            throw new \Exception('PDO connection required');
        }

        [$query, $binds] = $this->buildCreate();
        
        $statement = $this->getConnect()->prepare($query);
        $result = $statement->execute($binds);

        if ($result && $this->sequenceName) {
            $this->primaryKeyValue = $this->getConnect()->lastInsertId();
            $this->attributes[$this->primaryKeyName] = $this->primaryKeyValue;
        }

        return $result;
    }

    public function update()
    {
        if (!$this->getConnect()) {
            throw new \Exception('PDO connection required');
        }

        [$query, $binds] = $this->buildUpdate();
        
        $statement = $this->getConnect()->prepare($query);
        return $statement->execute($binds);
    }

    public function save()
    {
        if ($this->primaryKeyValue || isset($this->attributes[$this->primaryKeyName])) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public function delete()
    {
        if (!$this->getConnect()) {
            throw new \Exception('PDO connection required');
        }

        if (!$this->primaryKeyValue && !isset($this->attributes[$this->primaryKeyName])) {
            throw new \Exception('Primary key required for delete operation');
        }

        $primaryKeyValue = $this->primaryKeyValue ?? $this->attributes[$this->primaryKeyName];
        
        $query = "DELETE FROM {$this->tableName} WHERE {$this->primaryKeyName} = ?";
        $statement = $this->getConnect()->prepare($query);
        
        return $statement->execute([$primaryKeyValue]);
    }

    public function doDelete(): bool|self
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
                $this->originAttributes = [];
                return $this;
            }
        }
        return false;
    }

    protected function iteratorToDelete(array|self $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof self) {
                $attribute->delete();
            }
        }
    }

    public function objectToDelete(): bool
    {
        if (isset($this->attributes[$this->primaryKeyName])) {
            return $this->doDelete() !== false;
        }
        return false;
    }
}