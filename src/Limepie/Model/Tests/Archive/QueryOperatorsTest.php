<?php declare(strict_types=1);

namespace Limepie\Model\Tests;

use PHPUnit\Framework\TestCase;
use Limepie\Model\Constants\QueryOperators;

/**
 * QueryOperators 상수 클래스 테스트
 */
class QueryOperatorsTest extends TestCase
{
    public function testAllOperatorsAreValid()
    {
        $operators = QueryOperators::getAllOperators();
        
        $this->assertIsArray($operators);
        $this->assertNotEmpty($operators);
        $this->assertContains(QueryOperators::EQUAL, $operators);
        $this->assertContains(QueryOperators::LIKE, $operators);
        $this->assertContains(QueryOperators::IN, $operators);
    }

    public function testOperatorPattern()
    {
        $pattern = QueryOperators::getOperatorPattern();
        
        $this->assertIsString($pattern);
        $this->assertStringContainsString('Eq', $pattern);
        $this->assertStringContainsString('Like', $pattern);
        $this->assertStringContainsString('In', $pattern);
    }

    public function testSqlOperatorMapping()
    {
        $this->assertEquals('=', QueryOperators::toSqlOperator(QueryOperators::EQUAL));
        $this->assertEquals('!=', QueryOperators::toSqlOperator(QueryOperators::NOT_EQUAL));
        $this->assertEquals('LIKE', QueryOperators::toSqlOperator(QueryOperators::LIKE));
        $this->assertEquals('IN', QueryOperators::toSqlOperator(QueryOperators::IN));
        $this->assertEquals('IS NULL', QueryOperators::toSqlOperator(QueryOperators::IS_NULL));
    }

    public function testValidOperatorCheck()
    {
        $this->assertTrue(QueryOperators::isValidOperator(QueryOperators::EQUAL));
        $this->assertTrue(QueryOperators::isValidOperator(QueryOperators::LIKE));
        $this->assertFalse(QueryOperators::isValidOperator('InvalidOperator'));
    }

    public function testRequiresValue()
    {
        $this->assertTrue(QueryOperators::requiresValue(QueryOperators::EQUAL));
        $this->assertTrue(QueryOperators::requiresValue(QueryOperators::LIKE));
        $this->assertFalse(QueryOperators::requiresValue(QueryOperators::IS_NULL));
        $this->assertFalse(QueryOperators::requiresValue(QueryOperators::IS_NOT_NULL));
    }

    public function testOperatorConstants()
    {
        $this->assertEquals('Eq', QueryOperators::EQUAL);
        $this->assertEquals('Ne', QueryOperators::NOT_EQUAL);
        $this->assertEquals('Lt', QueryOperators::LESS_THAN);
        $this->assertEquals('Le', QueryOperators::LESS_THAN_EQUAL);
        $this->assertEquals('Gt', QueryOperators::GREATER_THAN);
        $this->assertEquals('Ge', QueryOperators::GREATER_THAN_EQUAL);
        $this->assertEquals('Like', QueryOperators::LIKE);
        $this->assertEquals('In', QueryOperators::IN);
        $this->assertEquals('NotIn', QueryOperators::NOT_IN);
        $this->assertEquals('IsNull', QueryOperators::IS_NULL);
        $this->assertEquals('IsNotNull', QueryOperators::IS_NOT_NULL);
    }
}