<?php

declare(strict_types=1);

namespace Limepie\Model\Traits;

use Limepie\Exception;

/**
 * Model 매직 메서드 처리 - __call을 통한 동적 메서드 생성
 */
trait MagicMethods
{
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
        if (0 === \strpos($name, 'possible')) {
            return $this->buildPossible($name, $arguments);
        }
        if (0 === \strpos($name, 'relations')) {
            return $this->buildRelation($name, $arguments, true);
        }
        if (0 === \strpos($name, 'relation')) {
            return $this->buildRelation($name, $arguments, false);
        }
        if (0 === \strpos($name, 'on')) {
            return $this->buildJoinOn($name, $arguments);
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
        if (0 === \strpos($name, 'new')) {
            return $this->buildNew($name, $arguments);
        }
        if (0 === \strpos($name, 'plus')) {
            return $this->buildPlus($name, $arguments);
        }
        if (0 === \strpos($name, 'minus')) {
            return $this->buildMinus($name, $arguments);
        }
        if (0 === \strpos($name, 'get')) {
            return $this->buildGetColumn($name, $arguments);
        }
        if (0 === \strpos($name, 'addRawColumn')) {
            return $this->buildAddRawColumn($name, $arguments);
        }

        throw (new Exception('"' . $name . '" method not found', 404))
            ->setDisplayMessage('page not found', __FILE__, __LINE__)
        ;
    }

    // 매직 메서드 빌더들 - 구현은 각각의 특화된 trait에서
    abstract protected function buildGroupBy($name, $arguments, $offset);
    abstract protected function buildOrderBy($name, $arguments, $offset);
    abstract protected function buildCondition($name, $arguments, $offset);
    abstract protected function buildAnd($name, $arguments, $offset);
    abstract protected function buildSum($name, $arguments, $offset);
    abstract protected function buildAvg($name, $arguments, $offset);
    abstract protected function buildOr($name, $arguments);
    abstract protected function buildKeyName($name, $arguments);
    abstract protected function buildAlias($name, $arguments);
    abstract protected function buildForceIndex($name, $arguments, $offset);
    abstract protected function buildMatch($name, $arguments);
    abstract protected function buildPossible($name, $arguments);
    abstract protected function buildRelation($name, $arguments, $isMany);
    abstract protected function buildJoinOn($name, $arguments);
    abstract protected function buildJoin($name, $arguments);
    abstract protected function buildGetBy($name, $arguments, $offset);
    abstract protected function buildCount($name, $arguments, $offset, $isGets = false);
    abstract protected function buildGetSum($name, $arguments, $offset);
    abstract protected function buildGetAvg($name, $arguments, $offset);
    abstract protected function buildGetsBy($name, $arguments, $offset);
    abstract protected function buildAddColumn($name, $arguments);
    abstract protected function buildRemoveColumn($name, $arguments);
    abstract protected function buildSetRaw($name, $arguments);
    abstract protected function buildSet($name, $arguments);
    abstract protected function buildNewRaw($name, $arguments);
    abstract protected function buildNew($name, $arguments);
    abstract protected function buildPlus($name, $arguments);
    abstract protected function buildMinus($name, $arguments);
    abstract protected function buildGetColumn($name, $arguments);
    abstract protected function buildAddRawColumn($name, $arguments);
    abstract protected function addAllColumns();
    abstract protected function get(...$arguments);
    abstract protected function gets(...$arguments);
}