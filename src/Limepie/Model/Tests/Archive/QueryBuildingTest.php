<?php

// Mock PDO for testing
class MockPDO extends PDO {
    public function __construct() {}
    public function prepare($statement, $options = null) {
        return new MockPDOStatement();
    }
}

class MockPDOStatement {
    public function execute($params = null) { return true; }
    public function fetch() { return ['id' => 1, 'name' => 'test']; }
    public function fetchAll() { return [['id' => 1, 'name' => 'test']]; }
}

// 필요한 모든 파일 로드 (상대 경로)
require_once __DIR__ . '/../Base/Core.php';
require_once __DIR__ . '/../Traits/MagicMethods.php';
require_once __DIR__ . '/../Traits/CrudOperations.php';
require_once __DIR__ . '/../Traits/QueryMethods.php';
require_once __DIR__ . '/../Constants/QueryOperators.php';
require_once __DIR__ . '/../ConditionBuilder.php';
require_once __DIR__ . '/../DataProcessor.php';
require_once __DIR__ . '/../QueryBuilder.php';
require_once __DIR__ . '/../RelationManager.php';
require_once __DIR__ . '/../SqlExecutor.php';
require_once __DIR__ . '/../../Model.php';

use Limepie\Model;

echo "=== 쿼리 빌더 테스트 ===\n\n";

try {
    // 기본 Model 설정
    $model = new Model(new MockPDO());
    $model->tableName = 'users';
    $model->tableAliasName = 'u1';
    $model->allColumns = ['id', 'name', 'email', 'age', 'status'];
    $model->primaryKeyName = 'id';

    echo "1. 기본 조건 쿼리 테스트\n";
    echo "========================\n";
    
    // WHERE 조건 테스트
    $model->whereNameEq('John');
    echo "WHERE name = 'John'\n";
    echo "조건: " . $model->condition . "\n";
    echo "바인드: " . print_r($model->binds, true) . "\n";

    // 새로운 모델로 AND 조건 테스트
    $model2 = new Model(new MockPDO());
    $model2->tableName = 'users';
    $model2->tableAliasName = 'u2';
    $model2->allColumns = ['id', 'name', 'email', 'age'];
    
    $model2->whereNameEq('John')
           ->andAgeGt(25)
           ->andStatusEq('active');
    
    echo "\n2. 복합 조건 테스트\n";
    echo "==================\n";
    echo "WHERE name = 'John' AND age > 25 AND status = 'active'\n";
    echo "조건: " . $model2->condition . "\n";
    echo "바인드: " . print_r($model2->binds, true) . "\n";

    // ORDER BY 테스트
    $model3 = new Model(new MockPDO());
    $model3->tableName = 'users';
    $model3->orderByName('ASC')
           ->limit(10, 5);
    
    echo "\n3. ORDER BY + LIMIT 테스트\n";
    echo "==========================\n";
    echo "ORDER BY: " . $model3->getOrderBy() . "\n";
    echo "LIMIT: " . $model3->getLimit() . "\n";

    // INSERT 쿼리 빌딩 테스트
    $model4 = new Model(new MockPDO());
    $model4->tableName = 'users';
    $model4->allColumns = ['id', 'name', 'email'];
    $model4->setAttributes(['name' => 'Jane', 'email' => 'jane@test.com']);
    $model4->dataStyles = ['name' => 'varchar', 'email' => 'varchar'];
    $model4->sequenceName = '';
    
    echo "\n4. INSERT 쿼리 빌딩 테스트\n";
    echo "=========================\n";
    [$insertQuery, $insertBinds] = $model4->buildCreate();
    echo "쿼리: " . $insertQuery . "\n";
    echo "바인드: " . print_r($insertBinds, true) . "\n";

    // UPDATE 쿼리 빌딩 테스트
    $model5 = new Model(new MockPDO());
    $model5->tableName = 'users';
    $model5->allColumns = ['id', 'name', 'email'];
    $model5->attributes = ['id' => 1, 'name' => 'Jane Updated', 'email' => 'jane.updated@test.com'];
    $model5->originAttributes = ['id' => 1, 'name' => 'Jane', 'email' => 'jane@test.com'];
    $model5->dataStyles = ['name' => 'varchar', 'email' => 'varchar'];
    
    echo "\n5. UPDATE 쿼리 빌딩 테스트\n";
    echo "=========================\n";
    [$updateQuery, $updateBinds] = $model5->buildUpdate();
    echo "쿼리: " . $updateQuery . "\n";
    echo "바인드: " . print_r($updateBinds, true) . "\n";

    // 복잡한 쿼리 테스트
    $model6 = new Model(new MockPDO());
    $model6->tableName = 'users';
    $model6->tableAliasName = 'u6';
    $model6->allColumns = ['id', 'name', 'email', 'age', 'status'];
    
    $model6->whereNameLike('%john%')
           ->andAgeGe(18)
           ->orStatusEq('premium')
           ->orderByCreatedTs('DESC')
           ->limit(0, 20);
    
    echo "\n6. 복잡한 쿼리 테스트\n";
    echo "====================\n";
    echo "WHERE name LIKE '%john%' AND age >= 18 OR status = 'premium' ORDER BY created_ts DESC LIMIT 20\n";
    echo "조건: " . $model6->condition . "\n";
    echo "바인드: " . print_r($model6->binds, true) . "\n";
    echo "정렬: " . $model6->getOrderBy() . "\n";
    echo "제한: " . $model6->getLimit() . "\n";

    // DELETE 쿼리 테스트
    $model7 = new Model(new MockPDO());
    $model7->tableName = 'users';
    $model7->whereIdEq(1);
    
    echo "\n7. DELETE 쿼리 테스트\n";
    echo "====================\n";
    echo "WHERE id = 1\n";
    echo "조건: " . $model7->condition . "\n";
    echo "바인드: " . print_r($model7->binds, true) . "\n";
    
    // Join 쿼리 테스트  
    $model8 = new Model(new MockPDO());
    $model8->tableName = 'users';
    $model8->tableAliasName = 'u';
    $model8->allColumns = ['id', 'name', 'profile_id'];
    
    echo "\n8. Join 쿼리 빌딩 테스트\n";
    echo "========================\n";
    echo "기본 조인 설정 완료\n";
    echo "테이블: " . $model8->tableName . " AS " . $model8->tableAliasName . "\n";

    // IN 조건 테스트
    $model9 = new Model(new MockPDO());
    $model9->tableName = 'users';
    $model9->whereIdIn([1, 2, 3, 4, 5]);
    
    echo "\n9. IN 조건 테스트\n";
    echo "=================\n";
    echo "WHERE id IN (1,2,3,4,5)\n";
    echo "조건: " . $model9->condition . "\n";
    echo "바인드: " . print_r($model9->binds, true) . "\n";

    // NOT NULL 조건 테스트
    $model10 = new Model(new MockPDO());
    $model10->tableName = 'users';
    $model10->whereEmailIsNotNull();
    
    echo "\n10. NOT NULL 조건 테스트\n";
    echo "========================\n";
    echo "WHERE email IS NOT NULL\n";
    echo "조건: " . $model10->condition . "\n";
    echo "바인드: " . print_r($model10->binds, true) . "\n";

    echo "\n=== 모든 테스트 완료 ===\n";

} catch (Exception $e) {
    echo "\n❌ 테스트 실패: " . $e->getMessage() . "\n";
    echo "파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "스택: " . $e->getTraceAsString() . "\n";
}