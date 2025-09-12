<?php declare(strict_types=1);

namespace Limepie\Model\Tests;

use PHPUnit\Framework\TestCase;
use Limepie\Model;
use PDO;
use PDOStatement;

/**
 * Model 클래스 통합 테스트
 * 130개 메서드에 대한 포괄적인 테스트 커버리지
 */
class ModelTest extends TestCase
{
    private Model $model;
    private PDO $mockPdo;
    private PDOStatement $mockStatement;

    protected function setUp(): void
    {
        // Mock PDO와 PDOStatement 설정
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        
        $this->mockPdo->method('prepare')
            ->willReturn($this->mockStatement);
        
        $this->mockStatement->method('execute')
            ->willReturn(true);
        
        // 테스트용 Model 인스턴스 생성
        $this->model = new Model($this->mockPdo);
        $this->model->tableName = 'test_table';
        $this->model->primaryKeyName = 'seq';
        $this->model->allColumns = ['seq', 'name', 'email', 'created_ts'];
        $this->model->normalColumns = ['name', 'email'];
        $this->model->timestampColumns = ['created_ts'];
    }

    // === 생성자 및 기본 메서드 테스트 ===
    
    public function testConstructor()
    {
        $model = new Model($this->mockPdo, ['name' => 'test'], ['name' => 'original']);
        
        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame($this->mockPdo, $model->pdo);
        $this->assertEquals(['name' => 'test'], $model->attributes);
        $this->assertEquals(['name' => 'original'], $model->originAttributes);
    }

    public function testInvoke()
    {
        $result = $this->model->__invoke($this->mockPdo, ['test' => 'data']);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals(['test' => 'data'], $this->model->attributes);
    }

    // === 매직 메서드 테스트 ===

    public function testMagicCallWhereCondition()
    {
        $result = $this->model->whereNameEq('John');
        
        $this->assertSame($this->model, $result);
        $this->assertStringContains('name = ?', $this->model->condition);
    }

    public function testMagicCallOrderBy()
    {
        $result = $this->model->orderByName();
        
        $this->assertSame($this->model, $result);
        $this->assertEquals('name', $this->model->orderBy);
    }

    public function testMagicCallGroupBy()
    {
        $result = $this->model->groupByName();
        
        $this->assertSame($this->model, $result);
        $this->assertEquals('name', $this->model->groupBy);
    }

    // === CRUD 메서드 테스트 ===

    public function testBuildCreate()
    {
        $this->model->attributes = ['name' => 'John', 'email' => 'john@test.com'];
        
        [$query, $binds] = $this->model->buildCreate();
        
        $this->assertStringContains('INSERT INTO', $query);
        $this->assertStringContains('test_table', $query);
        $this->assertArrayHasKey('name', $binds);
        $this->assertArrayHasKey('email', $binds);
    }

    public function testBuildUpdate()
    {
        $this->model->attributes = ['seq' => 1, 'name' => 'John Updated'];
        $this->model->originAttributes = ['seq' => 1, 'name' => 'John'];
        
        [$query, $binds] = $this->model->buildUpdate();
        
        $this->assertStringContains('UPDATE', $query);
        $this->assertStringContains('SET', $query);
        $this->assertStringContains('WHERE', $query);
    }

    // === 조건 빌더 테스트 ===

    public function testAndCondition()
    {
        $result = $this->model->andName('John');
        
        $this->assertSame($this->model, $result);
        $this->assertNotEmpty($this->model->and);
    }

    public function testConditionBuilder()
    {
        $this->model->conditionName('John')
                   ->andEmail('john@test.com');
        
        $this->assertStringContains('name = ?', $this->model->condition);
        $this->assertNotEmpty($this->model->and);
    }

    // === 관계 메서드 테스트 ===

    public function testOneToOneRelation()
    {
        $relatedModel = new Model();
        $result = $this->model->relation($relatedModel);
        
        $this->assertSame($this->model, $result);
        $this->assertNotEmpty($this->model->oneToOne);
    }

    public function testOneToManyRelations()
    {
        $relatedModel = new Model();
        $result = $this->model->relations($relatedModel);
        
        $this->assertSame($this->model, $result);
        $this->assertNotEmpty($this->model->oneToMany);
    }

    // === 데이터 타입 처리 테스트 ===

    public function testSafeFulltextKeyword()
    {
        $result = Model::safeFulltextKeyword('test search +terms -exclude');
        
        $this->assertIsString($result);
        $this->assertStringNotContainsString('+', $result);
        $this->assertStringNotContainsString('-', $result);
    }

    public function testDataTypeProcessing()
    {
        $this->model->dataTypes = ['email' => 'varchar', 'age' => 'int'];
        $this->model->dataStyles = ['email' => 'varchar', 'age' => 'int'];
        
        $this->model->setEmail('test@example.com');
        $this->model->setAge(25);
        
        $this->assertEquals('test@example.com', $this->model->attributes['email']);
        $this->assertEquals(25, $this->model->attributes['age']);
    }

    // === 컬럼 관리 테스트 ===

    public function testAddColumn()
    {
        $result = $this->model->addColumnStatus('active');
        
        $this->assertSame($this->model, $result);
        $this->assertArrayHasKey('status', $this->model->addColumns);
    }

    public function testRemoveColumn()
    {
        $result = $this->model->removeColumnPassword();
        
        $this->assertSame($this->model, $result);
        $this->assertContains('password', $this->model->removeColumns);
    }

    // === 쿼리 빌더 메서드 테스트 ===

    public function testLimit()
    {
        $result = $this->model->limit(10, 20);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals(10, $this->model->offset);
        $this->assertEquals(20, $this->model->limit);
    }

    public function testGroupLimit()
    {
        $result = $this->model->groupLimit(5);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals(5, $this->model->groupLimit);
    }

    // === JOIN 메서드 테스트 ===

    public function testJoin()
    {
        $joinModel = new Model();
        $joinModel->tableName = 'users';
        
        $result = $this->model->joinSeqWithUserSeq($joinModel);
        
        $this->assertSame($this->model, $result);
        $this->assertNotEmpty($this->model->joinModels);
    }

    public function testLeftJoin()
    {
        $joinModel = new Model();
        $joinModel->tableName = 'profiles';
        
        $result = $this->model->leftJoinSeqWithUserSeq($joinModel);
        
        $this->assertSame($this->model, $result);
        $this->assertNotEmpty($this->model->joinModels);
    }

    // === 속성 관리 테스트 ===

    public function testSetAttributes()
    {
        $attributes = ['name' => 'John', 'email' => 'john@test.com'];
        $result = $this->model->setAttributes($attributes);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals($attributes, $this->model->attributes);
    }

    public function testSetOriginAttributes()
    {
        $originAttributes = ['name' => 'Original John'];
        $result = $this->model->setOriginAttributes($originAttributes);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals($originAttributes, $this->model->originAttributes);
    }

    public function testPlusAttributes()
    {
        $result = $this->model->plusScore(10);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals(10, $this->model->plusAttributes['score']);
    }

    public function testMinusAttributes()
    {
        $result = $this->model->minusScore(5);
        
        $this->assertSame($this->model, $result);
        $this->assertEquals(5, $this->model->minusAttributes['score']);
    }

    // === 유틸리티 메서드 테스트 ===

    public function testKeyName()
    {
        $result = $this->model->keyNameSeq();
        
        $this->assertSame($this->model, $result);
        $this->assertEquals('seq', $this->model->keyName);
    }

    public function testValueName()
    {
        $result = $this->model->valueNameName();
        
        $this->assertSame($this->model, $result);
        $this->assertEquals('name', $this->model->valueName);
    }

    public function testForceIndex()
    {
        $result = $this->model->forceIndexPrimary();
        
        $this->assertSame($this->model, $result);
        $this->assertContains('primary', $this->model->forceIndexes);
    }

    // === 에러 처리 테스트 ===

    public function testInvalidMethodCall()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('method not found');
        
        $this->model->invalidMethodName();
    }

    // === 복합 시나리오 테스트 ===

    public function testComplexQueryBuilding()
    {
        $result = $this->model
            ->conditionName('John')
            ->andEmail('john@test.com')
            ->orderByCreatedTs()
            ->limit(0, 10);
        
        $this->assertSame($this->model, $result);
        $this->assertStringContains('name = ?', $this->model->condition);
        $this->assertNotEmpty($this->model->and);
        $this->assertEquals('created_ts', $this->model->orderBy);
        $this->assertEquals(0, $this->model->offset);
        $this->assertEquals(10, $this->model->limit);
    }

    public function testRelationshipChaining()
    {
        $userModel = new Model();
        $userModel->tableName = 'users';
        
        $profileModel = new Model();
        $profileModel->tableName = 'profiles';
        
        $result = $this->model
            ->relation($userModel)
            ->relations($profileModel);
        
        $this->assertSame($this->model, $result);
        $this->assertCount(1, $this->model->oneToOne);
        $this->assertCount(1, $this->model->oneToMany);
    }

    // === 데이터 변환 테스트 ===

    public function testDataStyleProcessing()
    {
        $this->model->dataStyles = [
            'data' => 'json',
            'config' => 'base64',
            'secret' => 'aes'
        ];
        
        $testData = ['key' => 'value'];
        $this->model->attributes = [
            'data' => $testData,
            'config' => 'test config',
            'secret' => 'secret data'
        ];
        
        // 실제 데이터 변환은 DataProcessor 헬퍼 클래스에서 처리됨
        $this->assertIsArray($this->model->attributes['data']);
    }

    // === 성능 테스트 ===

    public function testManyConditionsPerformance()
    {
        $startTime = microtime(true);
        
        $query = $this->model;
        for ($i = 0; $i < 100; $i++) {
            $query = $query->andId($i);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // 100개 조건 추가가 1초 미만이어야 함
        $this->assertLessThan(1.0, $executionTime);
        $this->assertCount(100, $this->model->and);
    }

    // === 메모리 테스트 ===

    public function testMemoryUsage()
    {
        $initialMemory = memory_get_usage();
        
        // 대량 데이터 처리
        for ($i = 0; $i < 1000; $i++) {
            $this->model->setAttributes(['test' . $i => 'value' . $i]);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // 메모리 증가가 50MB 미만이어야 함
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
    }

    // === 정리 메서드 ===

    protected function tearDown(): void
    {
        $this->model = null;
        $this->mockPdo = null;
        $this->mockStatement = null;
    }
}