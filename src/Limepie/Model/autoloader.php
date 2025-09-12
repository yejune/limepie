<?php

/**
 * Limepie Model 오토로더
 * - Model 컴포넌트들의 자동 로드 및 의존성 해결
 * - 개발, 테스트, 운영 환경에서 공통 사용 가능
 * - PSR-4 호환 오토로딩 시스템
 */

spl_autoload_register(function ($className) {
    // 로드 순서가 중요한 파일들을 미리 정의
    static $loadOrder = [
        'Limepie\\Exception'                     => '/../Exception.php',
        'Limepie\\Model\\Base\\Core'             => '/Base/Core.php',
        'Limepie\\Model\\Constants\\QueryOperators' => '/Constants/QueryOperators.php',
        'Limepie\\Model\\Traits\\MagicMethods'   => '/Traits/MagicMethods.php',
        'Limepie\\Model\\Traits\\CrudOperations' => '/Traits/CrudOperations.php', 
        'Limepie\\Model\\Traits\\QueryMethods'   => '/Traits/QueryMethods.php',
        'Limepie\\Model\\ConditionBuilder'       => '/ConditionBuilder.php',
        'Limepie\\Model\\DataProcessor'          => '/DataProcessor.php',
        'Limepie\\Model\\QueryBuilder'           => '/QueryBuilder.php',
        'Limepie\\Model\\RelationManager'        => '/RelationManager.php',
        'Limepie\\Model\\SqlExecutor'            => '/SqlExecutor.php',
        'Limepie\\Model'                         => '/../Model.php'
    ];
    
    // 정의된 클래스 목록에서 찾기
    if (isset($loadOrder[$className])) {
        $filePath = __DIR__ . $loadOrder[$className];
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
    
    // 일반적인 PSR-4 방식으로 시도
    if (strpos($className, 'Limepie\\') === 0) {
        $relativeClass = substr($className, 8); // 'Limepie\\' 제거
        $filePath = __DIR__ . '/../' . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});

// 로드 상태 확인 함수
function checkAutoload() {
    $classes = [
        'Limepie\\Exception',
        'Limepie\\Model\\Base\\Core',
        'Limepie\\Model\\Constants\\QueryOperators',
        'Limepie\\Model\\Traits\\MagicMethods',
        'Limepie\\Model\\Traits\\CrudOperations',
        'Limepie\\Model\\Traits\\QueryMethods',
        'Limepie\\Model\\ConditionBuilder',
        'Limepie\\Model\\DataProcessor',
        'Limepie\\Model\\QueryBuilder',
        'Limepie\\Model\\RelationManager',
        'Limepie\\Model\\SqlExecutor',
        'Limepie\\Model'
    ];
    
    $loaded = 0;
    foreach ($classes as $class) {
        if (class_exists($class) || trait_exists($class)) {
            $loaded++;
        } else {
            echo "⚠️ {$class} 로드 실패\n";
        }
    }
    
    echo "✅ {$loaded}/" . count($classes) . " 클래스 로드 완료\n";
    return $loaded === count($classes);
}

// 디버그 모드일 때 로드 상태 출력
if (defined('AUTOLOAD_DEBUG')) {
    checkAutoload();
}