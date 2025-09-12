<?php

// Mock PDO for testing (global namespace)
class MockPDO extends PDO {
    public function __construct() {}
    public function prepare($statement, $options = null) {
        return new MockPDOStatement();
    }
}

class MockPDOStatement {
    public function execute($params = null) { return true; }
    public function fetch() { return ['seq' => 1, 'name' => 'test', 'service_seq' => 1]; }
    public function fetchAll() { return [
        ['seq' => 1, 'name' => 'ServiceModule', 'service_seq' => 1],
        ['seq' => 2, 'name' => 'Container', 'container_seq' => 1],
        ['seq' => 3, 'name' => 'User', 'user_seq' => 1]
    ]; }
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

echo "=== 복잡한 Relations 테스트 ===\n\n";

try {
    // ServiceModule 모델 (실제 구조 기반)
    $serviceModule = new Model(new MockPDO());
    $serviceModule->tableName = 'service_module';
    $serviceModule->tableAliasName = 'sm';
    $serviceModule->primaryKeyName = 'seq';
    $serviceModule->allColumns = ['seq', 'service_seq', 'id', 'name', 'created_ts'];

    echo "1. 기본 ServiceModule 모델 설정\n";
    echo "==============================\n";
    echo "테이블: {$serviceModule->tableName} AS {$serviceModule->tableAliasName}\n";
    echo "PK: {$serviceModule->primaryKeyName}\n\n";

    // Container 모델
    $container = new Model(new MockPDO());
    $container->tableName = 'container';
    $container->tableAliasName = 'c1';
    $container->primaryKeyName = 'seq';
    $container->allColumns = ['seq', 'service_module_seq', 'user_seq', 'title', 'content', 'created_ts'];
    
    echo "2. Container Relations 테스트\n";
    echo "=============================\n";

    // 복잡한 조건 체이닝 테스트 (실제 사용 패턴)
    $container->matchSeqWithServiceModuleSeq()
             ->andGeCreatedTs(date('Y-m-d H:i:s', time() - (86400 * 30)))
             ->andIsCloseComment(0)
             ->orderBySeqDesc();

    echo "조건: " . $container->condition . "\n";
    echo "바인드: " . print_r($container->binds, true) . "\n";

    // User 모델
    $user = new Model(new MockPDO());
    $user->tableName = 'user';
    $user->tableAliasName = 'u1';
    $user->primaryKeyName = 'seq';
    $user->allColumns = ['seq', 'username', 'email', 'created_ts'];

    echo "\n3. User Alias 테스트\n";
    echo "===================\n";
    $user->matchUserSeqWithSeq()->aliasUser();
    echo "조건: " . $user->condition . "\n";
    echo "Alias: " . $user->tableAliasName . "\n";

    // ServiceModuleCategoryItem 모델
    $categoryItem = new Model(new MockPDO());
    $categoryItem->tableName = 'service_module_category_item';
    $categoryItem->tableAliasName = 'smci';
    $categoryItem->primaryKeyName = 'seq';
    $categoryItem->allColumns = ['seq', 'service_module_seq', 'name', 'parent_seq', 'order_number'];

    echo "\n4. Category Relations 테스트\n";
    echo "============================\n";
    $categoryItem->matchServiceModuleCategoryItemSeqWithSeq()
                 ->aliasItem()
                 ->orderByOrderNumberAsc();
    
    echo "조건: " . $categoryItem->condition . "\n";
    echo "Alias: " . $categoryItem->tableAliasName . "\n";

    // 복잡한 매직 메서드 테스트
    echo "\n5. 복잡한 매직 메서드 테스트\n";
    echo "===========================\n";

    $productModel = new Model(new MockPDO());
    $productModel->tableName = 'product';
    $productModel->allColumns = ['seq', 'name', 'price', 'is_sale', 'is_display', 'created_ts'];

    // 실제 사용 패턴: getByServiceSeqAndId 같은 복합 조건
    $productModel->whereServiceSeqEq(1)
                 ->andIdEq('product_001')
                 ->andIsSaleEq(1)
                 ->andIsDisplayEq(1);

    echo "복합 조건: " . $productModel->condition . "\n";
    echo "바인드: " . print_r($productModel->binds, true) . "\n";

    // ORDER BY 및 LIMIT 테스트
    echo "\n6. ORDER BY + LIMIT 조합 테스트\n";
    echo "==============================\n";

    $listModel = new Model(new MockPDO());
    $listModel->tableName = 'battle';
    $listModel->whereIsDisplayEq(1)
              ->andGtCreatedTs('2024-01-01')
              ->orderBySeqDesc()
              ->limit(0, 10);

    echo "조건: " . $listModel->condition . "\n";
    echo "정렬: " . $listModel->orderBy . "\n";
    echo "제한: LIMIT {$listModel->limit}" . ($listModel->offset !== null ? ", {$listModel->offset}" : "") . "\n";

    // IN 조건과 복합 조건 테스트
    echo "\n7. IN 조건과 복합 AND/OR 테스트\n";
    echo "==============================\n";

    $complexModel = new Model(new MockPDO());
    $complexModel->tableName = 'product_review';
    $complexModel->whereProductSeqIn([1, 2, 3, 4, 5])
                 ->andIsDisplayEq(1)
                 ->orStarGe(4)
                 ->andContentIsNotNull();

    echo "복합 조건: " . $complexModel->condition . "\n";
    echo "바인드: " . print_r($complexModel->binds, true) . "\n";

    // 실제 SQL JOIN 테스트
    echo "\n8. 실제 SQL JOIN 테스트 (joinSeqWithParentSeq)\n";
    echo "=============================================\n";
    
    $joinModel = new Model(new MockPDO());
    $joinModel->tableName = 'category';
    $joinModel->tableAliasName = 'c1';
    $joinModel->joinSeqWithParentSeq()  // 실제 JOIN 구문
              ->whereIsDisplayEq(1);
              
    echo "JOIN 조건: " . $joinModel->condition . "\n";
    echo "바인드: " . print_r($joinModel->binds, true) . "\n";

    // Alias 체이닝 테스트  
    echo "\n9. Alias 체이닝 테스트\n";
    echo "=====================\n";

    $aliasTest = new Model(new MockPDO());
    $aliasTest->tableName = 'battle_player';
    $aliasTest->aliasPlayer()
              ->aliasBattlePlayer() 
              ->aliasGamePlayer();
              
    echo "최종 Alias: " . $aliasTest->tableAliasName . "\n";

    // Relations vs Join 차이점 설명
    echo "\n10. Relations vs JOIN 차이점\n";
    echo "============================\n";
    echo "- relations(): 별도 쿼리로 데이터 가져와서 PHP에서 조합\n";
    echo "- joinSeqWithXxx(): 실제 SQL JOIN 사용\n";
    echo "\nRelations 예제 (Battle 컨트롤러 패턴):\n";
    echo "ServiceModule->relations(Container->relations(CategoryItem))\n";
    echo "= 3개의 개별 SELECT 쿼리 실행 후 PHP에서 연결\n\n";

    // 실제 데이터베이스 패턴: seq 기반 관계
    echo "11. SEQ 기반 관계 매칭 패턴\n";
    echo "===========================\n";

    $seqModel = new Model(new MockPDO());
    $seqModel->tableName = 'order_product_item';
    $seqModel->matchSeqWithProductSeq()          // seq = product_seq
             ->matchUserSeqWithOrderUserSeq()     // user_seq = order_user_seq  
             ->matchCompanySeqWithStoreSeq();     // company_seq = store_seq

    echo "SEQ 매칭 조건들: " . $seqModel->condition . "\n";
    echo "바인드: " . print_r($seqModel->binds, true) . "\n";

    echo "\n=== 모든 복잡한 Relations 테스트 완료 ===\n";

} catch (Exception $e) {
    echo "\n❌ 테스트 실패: " . $e->getMessage() . "\n";
    echo "파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "스택: " . $e->getTraceAsString() . "\n";
}