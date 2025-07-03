<?php

declare(strict_types=1);

namespace Limepie;

use Limepie\Pdo\Exception\OptimisticLock;

class Model extends ModelUtil
{
    /**
     * MySQL 풀텍스트 검색에서 사용할 수 있도록 검색어를 안전하게 만드는 함수.
     *
     * @param string $searchTerm 원래 검색어
     *
     * @return string 풀텍스트 검색에 안전한 정제된 검색어
     */
    public static function safe_fulltext_keyword($searchTerm)
    {
        // 공백 제거
        $searchTerm = \trim($searchTerm);

        if (empty($searchTerm)) {
            return '';
        }

        // MySQL 풀텍스트 부울 연산자 제거 (구문 오류 방지)
        $operators  = ['+', '-', '<', '>', '(', ')', '~', '*', '"', '@', '>', '<'];
        $searchTerm = \str_replace($operators, ' ', $searchTerm);

        // 여러 공백을 하나로 통합
        $searchTerm = \preg_replace('/\s+/', ' ', $searchTerm);

        // 단어로 분리
        $words     = \explode(' ', $searchTerm);
        $safeWords = [];

        foreach ($words as $word) {
            $word = \trim($word);

            // 빈 단어 건너뛰기
            if (empty($word)) {
                continue;
            }

            // 너무 짧은 단어 건너뛰기 (MySQL에서 일반적으로 3글자 미만)
            // MySQL의 ft_min_word_len 설정에 따라 조정이 필요할 수 있음
            if (\mb_strlen($word) >= 3) {
                // SQL 인젝션 방지를 위한 특수 문자 제거
                $word = \preg_replace('/[^\p{L}\p{N}]/u', ' ', $word);

                if (!empty($word)) {
                    $safeWords[] = $word;
                }
            }
        }

        // 단어들을 결합
        if (!empty($safeWords)) {
            return \implode(' ', $safeWords);
        }

        return '';
    }

    public function replace() {}

    public function buildCreate($prefix = '')
    {
        $columns = [];
        $binds   = [];
        $values  = [];

        foreach ($this->allColumns as $column) {
            $columnBindName = $prefix . $column;

            if ($this->sequenceName === $column) {
            } else {
                // create시에는 시간 컬럼 추가하지 않음, 자동 입력
                if ('created_ts' === $column || 'updated_ts' === $column) {
                    if ($this->attributes[$column] ?? false) {
                        // $columns[]            = '`' . $column . '`';
                        // $values[]             = ':' . $column;
                        // $binds[':' . $column] = $this->attributes[$column];
                    }
                } elseif ('ip' === $column) {
                    $columns[]                    = '`' . $column . '`';
                    $binds[':' . $columnBindName] = $this->attributes[$column] ?? \Limepie\getIp();
                    $values[]                     = 'inet6_aton(:' . $columnBindName . ')';
                } elseif ('aes_serialize' === $this->dataStyles[$column]) {
                    $columns[]                                   = '`' . $column . '`';
                    $binds[':' . $columnBindName]                = \serialize($this->attributes[$column] ?? null);
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
                    $values[]                                    = 'AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey)';
                } elseif ('aes' === $this->dataStyles[$column]) {
                    $columns[]                                   = '`' . $column . '`';
                    $binds[':' . $columnBindName]                = $this->attributes[$column] ?? null;
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
                    $values[]                                    = 'AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey)';
                } elseif ('aes_hex' === $this->dataStyles[$column]) {
                    $columns[]                                   = '`' . $column . '`';
                    $binds[':' . $columnBindName]                = $this->attributes[$column] ?? null;
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
                    $values[]                                    = 'HEX(AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey))';
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
                    $binds[':' . $columnBindName . '1'] = $value[0];
                    $binds[':' . $columnBindName . '2'] = $value[1];

                    $values[] = 'point(:' . $columnBindName . '1, :' . $columnBindName . '2)';
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
                        // raw는
                        // ->setRawLocation(
                        //     'POINT(:x, :y)',
                        //     [
                        //         ':x' => $point['x'],
                        //         ':y' => $point['y'],
                        //     ]
                        // )
                        // 형태나 ? 형태로 들어오므로 bind변수를 prefix할 필요가 없음.

                        $columns[] = "`{$this->tableName}`." . '`' . $column . '`';
                        $values[]  = \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $binds += $value;
                        } else {
                            throw new Exception($column . ' raw bind error');
                        }
                    } else {
                        $columns[]                    = '`' . $column . '`';
                        $binds[':' . $columnBindName] = $value;
                        $values[]                     = ':' . $columnBindName;
                    }
                }
            }
        }

        return [$columns, $binds, $values];
    }

    public function create($on_duplicate_key_update = null)
    {
        $columns = [];
        $binds   = [];
        $values  = [];

        [$columns, $binds, $values] = $this->buildCreate($on_duplicate_key_update);

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

        if ($this->duplication) {
            [$duplicationColumns, $duplicationBinds, $duplicationSames] = $this->duplication->buildUpdate('dup_');

            $sql .= ' ON DUPLICATE KEY UPDATE ' . \implode(', ', $duplicationColumns);
            $binds += $duplicationBinds;
        }

        $primaryKey = '';

        if (static::$debug) {
            $this->print($sql, $binds);
            Timer::start();
        }
        $this->query = $sql;
        $this->binds = $binds;

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

    public function buildUpdate($prefix = '')
    {
        $columns = [];
        $binds   = [];
        $sames   = [];

        foreach ($this->allColumns as $column) {
            $columnBindName = $prefix . $column;
            // db에서 가져온것과 비교해서 바뀌지 않으면 업데이트 하지 않음

            $attr     = $this->attributes[$column]       ?? null;
            $origAttr = $this->originAttributes[$column] ?? null;

            // attr과 originAttr가 같고 raw, plus, minus가 아니면 continue,
            // raw, plus, minus가 있으면 값은 변동되지 않아도 업데이트 함
            // $this->originAttributes가 있다는건 셀렉트를 했다는것. create후 update시에는 존재하지 않는 값
            if (
                $this->originAttributes
                && $attr === $origAttr
                && false === isset($this->plusAttributes[$column])
                && false === isset($this->minusAttributes[$column])
                && false === isset($this->rawAttributes[$column])
            ) {
                $sames[$column] = $origAttr;

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
                    if ('created_ts' === $column) {
                        // 입력 날짜는 변경 안함
                    } else {
                        // 수정 날짜는 변경 할수있음.
                        if ($this->attributes[$column] ?? false) {
                            $columns[]                    = "`{$this->tableName}`." . '`' . $column . '` = :' . $columnBindName;
                            $binds[':' . $columnBindName] = $this->attributes[$column];
                        }
                    }
                } elseif ('ip' === $column) {
                    $columns[]                    = "`{$this->tableName}`." . '`' . $column . '` = inet6_aton(:' . $columnBindName . ')';
                    $binds[':' . $columnBindName] = $this->attributes[$column] ?? \Limepie\getIp();
                } elseif ('aes_serialize' === $this->dataStyles[$column]) {
                    $columns[]                                   = "`{$this->tableName}`." . '`' . $column . '` = AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey)';
                    $binds[':' . $columnBindName]                = \serialize($this->attributes[$column] ?? null);
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
                } elseif ('aes' === $this->dataStyles[$column]) {
                    $columns[]                                   = "`{$this->tableName}`." . '`' . $column . '` = AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey)';
                    $binds[':' . $columnBindName]                = $this->attributes[$column] ?? null;
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
                } elseif ('aes_hex' === $this->dataStyles[$column]) {
                    $columns[]                                   = "`{$this->tableName}`." . '`' . $column . '` = HEX(AES_ENCRYPT(:' . $columnBindName . ', :' . $columnBindName . '_secretkey))';
                    $binds[':' . $columnBindName]                = $this->attributes[$column] ?? null;
                    $binds[':' . $columnBindName . '_secretkey'] = Aes::$salt;
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

                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = point(:' . $columnBindName . '1, :' . $columnBindName . '2)';

                        $binds[':' . $columnBindName . '1'] = $value[0];
                        $binds[':' . $columnBindName . '2'] = $value[1];
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
                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . "`{$this->tableName}`." . '`' . $column . '` + ' . $this->plusAttributes[$column];
                    } elseif (true === isset($this->minusAttributes[$column])) {
                        $name = "`{$this->tableName}`." . '`' . $column . '`';

                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . "IF({$name} > 0, {$name} - " . $this->minusAttributes[$column] . ', 0)';
                    } elseif (true === isset($this->rawAttributes[$column])) {
                        // raw는
                        // ->setRawLocation(
                        //     'POINT(:x, :y)',
                        //     [
                        //         ':x' => $point['x'],
                        //         ':y' => $point['y'],
                        //     ]
                        // )
                        // 형태나 ? 형태로 들어오므로 bind변수를 prefix할 필요가 없음.
                        // ? 의 경우에 대한 보완 필요. 겹칠수도 있음.

                        $columns[] = "`{$this->tableName}`." . '`' . $column . '` = ' . \str_replace('?', ':' . $column, $this->rawAttributes[$column]);

                        if (null === $value) {
                        } elseif (true === \is_array($value)) {
                            $binds += $value;
                        } else {
                            throw new Exception($column . ' raw bind error');
                        }
                    } else {
                        $columns[]                    = "`{$this->tableName}`." . '`' . $column . '` = :' . $columnBindName;
                        $binds[':' . $columnBindName] = $value;
                    }
                }
            }
        }

        return [$columns, $binds, $sames];
    }

    public function update($checkUpdatedTs = false)
    {
        if (!$this->primaryKeyValue) {
            $debug = \debug_backtrace()[0];

            throw (new Exception('not found ' . $this->primaryKeyName))
                ->setDebugMessage('models update?', $debug['file'], $debug['line'])
            ;
        }

        [$this->changeColumns, $this->changeBinds, $this->sameColumns] = $this->buildUpdate();

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

            $this->changeBinds[':' . $this->primaryKeyName] = $this->primaryKeyValue;

            if (true === $checkUpdatedTs) {
                $sql .= ' AND updated_ts = :check_updated_ts';
                $this->changeBinds[':check_updated_ts'] = $this->attributes['updated_ts'];
            }

            if (static::$debug) {
                $this->print($sql, $this->changeBinds);
                Timer::start();
            }

            if ($this->getConnect()->set($sql, $this->changeBinds)) {
                if (true === $checkUpdatedTs && 0 == $this->getConnect()->last_row_count()) {
                    throw new OptimisticLock($this->tableName . ' updated_ts is changed');
                }

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

            // if ($this->rawColumnString) {
            //     $selectColumns .= ',' . $this->rawColumnString;
            // }
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

        $orderBy    = $this->getOrderBy();
        $limit      = $this->getLimit();
        $groupLimit = $this->getGroupLimit();
        $join       = '';

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

        // if ($this->rawColumnString) {
        //     $selectColumns .= ',' . $this->rawColumnString;
        // }

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
                        if ('seq' !== $key && \Limepie\has_value($value)) {
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

            $this->primaryKeyValue = $attributes[$this->primaryKeyName] ?? null;
            // originAttr과 attr이 달라지는 경우는 valueName을 적용하거나 parentNode를 적용한 경우
            // parentNode는 원본을 상실함. 부모노드로 속성만 옮겨지고 구조가 유지 되지 않음
            // 여기서는 valueName을 적용할수 없고 getRelation에서 배열에서 적용하는 구조이므로
            // originAttributes와 attributes는 동일한 배열을 참조함
            $this->originAttributes = $this->attributes = $this->getRelation($attributes);

            if ($this->valueName instanceof \Closure) {
                if (\is_array($this->attributes)) {
                    $fetchValue = ($this->valueName)($this);

                    $this->attributes = $fetchValue;
                }
            }

            if ($this->addColumns) {
                if (\is_array($this->attributes)) {
                    foreach ($this->addColumns as $columnName => $aliasName) {
                        if ($aliasName instanceof \Closure) {
                            $this->attributes[$columnName] = $aliasName($this);
                        }
                    }
                }
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
        $groupLimit = $this->getGroupLimit();
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

        // if ($this->rawColumnString) {
        //     $selectColumns .= ',' . $this->rawColumnString;
        // }

        $sql = '';

        if ($groupLimit) {
            $sql = <<<SQL
                SELECT
                {$selectColumns}
                ,
                ROW_NUMBER() OVER (PARTITION BY `{$this->tableAliasName}`.`{$this->rightKeyName}` {$orderBy}) as row_num
            SQL;
        } else {
            $sql = <<<SQL
                SELECT
                {$selectColumns}

            SQL;
        }
        $sql .= <<<SQL

            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
                {$forceIndex}
                {$join}
                {$condition}
                {$groupBy}
                {$orderBy}
        SQL;

        if ($groupLimit) {
            $sql = <<<SQL
                SELECT *
                FROM (
                    {$sql}
                ) AS ranked
                WHERE ranked.row_num <= {$groupLimit}
            SQL;
        } else {
            $sql .= ' ' . $limit;
        }

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

            // if ($this->rawColumnString) {
            //     $selectColumns .= ',' . $this->rawColumnString;
            // }

            $groupLimit = $this->getGroupLimit();

            if ($groupLimit) {
                $sql = <<<SQL
                SELECT
                {$selectColumns}
                ,
                ROW_NUMBER() OVER (PARTITION BY `{$this->tableAliasName}`.`{$this->rightKeyName}` {$orderBy}) as row_num
            SQL;
            } else {
                $sql = <<<SQL
                SELECT
                {$selectColumns}

            SQL;
            }
            $sql .= <<<SQL

            FROM
                `{$this->tableName}` AS `{$this->tableAliasName}`
                {$forceIndex}
                {$join}
                {$condition}
                {$groupBy}
                {$orderBy}
        SQL;

            if ($groupLimit) {
                $sql = <<<SQL
                SELECT *
                FROM (
                    {$sql}
                ) AS ranked
                WHERE ranked.row_num <= {$groupLimit}
            SQL;
            } else {
                $sql .= ' ' . $limit;
            }

            // $sql = <<<SQL
            //     SELECT
            //         {$selectColumns}
            //     FROM
            //         `{$this->tableName}` AS `{$this->tableAliasName}`
            //     {$forceIndex}
            //     {$join}
            //     {$condition}
            //     {$groupBy}
            //     {$orderBy}
            //     {$limit}
            // SQL;
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
                        if ('seq' !== $key && \Limepie\has_value($value)) {
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

                // $row[$parentTableName] = new $joinModel($this->getConnect(), $tmp, null, true);

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
                        // if ($parentTableName) {
                        //     throw new Exception('gets ' . $this->tableName . ' "> ' . $parentTableName . ' ' . $this->keyName . '" column not found #5');
                        // }

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
            // gets에서는 원본 속성을 저장하지 않음, 원본속성은 개별 모델에만 존재함
            $this->originAttributes = [];

            $attributes = $this->getRelations($attributes);

            if ($this->valueName instanceof \Closure) {
                foreach ($attributes as $key => $attribute) {
                    if (\is_array($attributes[$key]->attributes)) {
                        $fetchValue = ($this->valueName)($attributes[$key]);
                        // echo '<pre>---';
                        // \print_r($key);
                        // echo PHP_EOL;
                        // \print_r($fetchValue);
                        // echo '---</pre>';
                        $attributes[$key]->attributes = $fetchValue;
                    }
                }
            }

            if ($this->addColumns) {
                foreach ($attributes as $key => $attribute) {
                    if (\is_array($attributes[$key]->attributes)) {
                        foreach ($this->addColumns as $columnName => $aliasName) {
                            if ($aliasName instanceof \Closure) {
                                $attributes[$key]->attributes[$columnName] = ($aliasName)($attributes[$key]);
                            }
                        }
                    }
                }
            }

            $this->attributes = $attributes;

            return $this;
        }

        return $this->empty();
    }
}
