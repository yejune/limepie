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
    public function fetch() { return ['seq' => 1, 'name' => 'test', 'service_seq' => 1]; }
    public function fetchAll() { return [
        ['seq' => 1, 'name' => 'Battle Service', 'service_seq' => 1, 'id' => 'battle'],
        ['seq' => 2, 'name' => 'Notice Service', 'service_seq' => 1, 'id' => 'notice']
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

echo "=== 매직 메서드 테스트 ===\n\n";

try {
    // 1. getBy 복합 조건 테스트 (실제 사용 패턴)
    echo "1. getBy 복합 조건 테스트\n";
    echo "=========================\n";

    $serviceModule = new Model(new MockPDO());
    $serviceModule->tableName = 'service_module';
    $serviceModule->allColumns = ['seq', 'service_seq', 'id', 'name'];

    // Battle 컨트롤러에서 실제 사용: getByServiceSeqAndId()
    echo "getByServiceSeqAndId(1, 'notice') 호출:\n";
    
    // 매직 메서드가 제대로 파싱하는지 확인
    $serviceModule->whereServiceSeqEq(1)->andIdEq('notice');
    echo "파싱된 조건: " . $serviceModule->condition . "\n";
    echo "바인드: " . print_r($serviceModule->binds, true) . "\n";

    // 2. match 시리즈 매직 메서드 테스트
    echo "2. match 시리즈 매직 메서드 테스트\n";
    echo "=================================\n";

    $container = new Model(new MockPDO());
    $container->tableName = 'container';
    $container->allColumns = ['seq', 'service_module_seq', 'user_seq'];

    // 실제 사용 패턴: matchSeqWithServiceModuleSeq
    $container->matchSeqWithServiceModuleSeq();
    echo "matchSeqWithServiceModuleSeq() 조건: " . $container->condition . "\n";
    echo "바인드: " . print_r($container->binds, true) . "\n";

    // 3. 여러 match 조건 체이닝
    echo "\n3. 여러 match 조건 체이닝 테스트\n";
    echo "==============================\n";

    $orderItem = new Model(new MockPDO());
    $orderItem->tableName = 'order_product_item';
    $orderItem->allColumns = ['seq', 'product_seq', 'user_seq', 'company_seq'];

    $orderItem->matchSeqWithProductSeq()
              ->matchUserSeqWithOrderUserSeq()
              ->matchCompanySeqWithStoreSeq();

    echo "3개 match 체이닝 조건: " . $orderItem->condition . "\n";
    echo "바인드: " . print_r($orderItem->binds, true) . "\n";

    // 4. 복잡한 gets 시리즈 테스트
    echo "\n4. gets 시리즈 매직 메서드 테스트\n";
    echo "================================\n";

    $battlePlayer = new Model(new MockPDO());
    $battlePlayer->tableName = 'battle_player';
    $battlePlayer->allColumns = ['seq', 'battle_seq', 'user_seq', 'score'];

    // 실제 사용 패턴: getsByBattleSeq(1)
    $battlePlayer->whereBattleSeqEq(1)->orderByScoreDesc();
    echo "getsByBattleSeq(1) with orderByScoreDesc():\n";
    echo "조건: " . $battlePlayer->condition . "\n";
    echo "정렬: " . $battlePlayer->orderBy . "\n";
    echo "바인드: " . print_r($battlePlayer->binds, true) . "\n";

    // 5. Count 시리즈 테스트
    echo "\n5. Count 시리즈 매직 메서드 테스트\n";
    echo "=================================\n";

    $product = new Model(new MockPDO());
    $product->tableName = 'product';
    $product->allColumns = ['seq', 'company_seq', 'is_display', 'is_sale'];

    // 실제 사용 패턴: getCountByCompanySeq(1)
    $product->whereCompanySeqEq(1)->andIsDisplayEq(1)->andIsSaleEq(1);
    echo "getCountByCompanySeq(1) with display/sale filters:\n";
    echo "조건: " . $product->condition . "\n";
    echo "바인드: " . print_r($product->binds, true) . "\n";

    // 6. 복잡한 OR 조건과 함께 사용
    echo "\n6. OR 조건과 복합 매직 메서드\n";
    echo "============================\n";

    $review = new Model(new MockPDO());
    $review->tableName = 'product_review';
    $review->allColumns = ['seq', 'product_seq', 'user_seq', 'star', 'is_display'];

    $review->whereProductSeqEq(1)
           ->andIsDisplayEq(1)
           ->orStarGe(4)
           ->orUserSeqEq(100);

    echo "복합 WHERE + OR 조건:\n";
    echo "조건: " . $review->condition . "\n";
    echo "바인드: " . print_r($review->binds, true) . "\n";

    // 7. 실제 Battle GetList 패턴 재현
    echo "\n7. Battle GetList 실제 패턴 재현\n";
    echo "===============================\n";

    $realPattern = new Model(new MockPDO());
    $realPattern->tableName = 'service_module';
    $realPattern->allColumns = ['seq', 'service_seq', 'id', 'name'];

    // Battle/Plugin/GetList.php의 실제 패턴
    $realPattern->whereServiceSeqEq(1)  // Di::getServiceModel()->getSeq()
               ->andIdEq('notice');     // 'notice' 

    echo "실제 Battle GetList 패턴:\n";
    echo "getByServiceSeqAndId(1, 'notice') 대신:\n";
    echo "조건: " . $realPattern->condition . "\n";
    echo "바인드: " . print_r($realPattern->binds, true) . "\n";

    echo "\n=== 모든 매직 메서드 테스트 완료 ===\n";

} catch (Exception $e) {
    echo "\n❌ 테스트 실패: " . $e->getMessage() . "\n";
    echo "파일: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "스택: " . $e->getTraceAsString() . "\n";
}