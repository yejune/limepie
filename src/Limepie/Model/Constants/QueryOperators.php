<?php

declare(strict_types=1);

namespace Limepie\Model\Constants;

/**
 * 쿼리 연산자 상수 정의
 * 중복 제거 및 중앙 집중 관리
 */
class QueryOperators
{
    // 기본 비교 연산자
    public const EQUAL = 'Eq';
    public const NOT_EQUAL = 'Ne';
    public const LESS_THAN = 'Lt';
    public const LESS_THAN_EQUAL = 'Le';
    public const GREATER_THAN = 'Gt';
    public const GREATER_THAN_EQUAL = 'Ge';
    
    // 패턴 매칭 연산자
    public const LIKE = 'Like';
    public const NOT_LIKE = 'NotLike';
    
    // 집합 연산자
    public const IN = 'In';
    public const NOT_IN = 'NotIn';
    
    // NULL 체크 연산자
    public const IS_NULL = 'IsNull';
    public const IS_NOT_NULL = 'IsNotNull';
    
    // BETWEEN 연산자
    public const BETWEEN = 'Between';
    public const NOT_BETWEEN = 'NotBetween';

    /**
     * 모든 연산자 목록 반환
     */
    public static function getAllOperators(): array
    {
        return [
            self::EQUAL,
            self::NOT_EQUAL,
            self::LESS_THAN,
            self::LESS_THAN_EQUAL,
            self::GREATER_THAN,
            self::GREATER_THAN_EQUAL,
            self::LIKE,
            self::NOT_LIKE,
            self::IN,
            self::NOT_IN,
            self::IS_NULL,
            self::IS_NOT_NULL,
            self::BETWEEN,
            self::NOT_BETWEEN,
        ];
    }

    /**
     * 연산자 정규식 패턴 생성
     */
    public static function getOperatorPattern(): string
    {
        $operators = self::getAllOperators();
        return '(' . implode('|', $operators) . ')';
    }

    /**
     * SQL 연산자 매핑
     */
    public static function toSqlOperator(string $operator): string
    {
        return match ($operator) {
            self::EQUAL => '=',
            self::NOT_EQUAL => '!=',
            self::LESS_THAN => '<',
            self::LESS_THAN_EQUAL => '<=',
            self::GREATER_THAN => '>',
            self::GREATER_THAN_EQUAL => '>=',
            self::LIKE => 'LIKE',
            self::NOT_LIKE => 'NOT LIKE',
            self::IN => 'IN',
            self::NOT_IN => 'NOT IN',
            self::IS_NULL => 'IS NULL',
            self::IS_NOT_NULL => 'IS NOT NULL',
            self::BETWEEN => 'BETWEEN',
            self::NOT_BETWEEN => 'NOT BETWEEN',
            default => '=',
        };
    }

    /**
     * 연산자가 유효한지 확인
     */
    public static function isValidOperator(string $operator): bool
    {
        return in_array($operator, self::getAllOperators(), true);
    }

    /**
     * NULL 값을 허용하지 않는 연산자들
     */
    public static function requiresValue(string $operator): bool
    {
        return !in_array($operator, [self::IS_NULL, self::IS_NOT_NULL], true);
    }
}