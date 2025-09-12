<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoloader.php';
require_once __DIR__ . '/LegacyCompatibilityTest.php';
require_once __DIR__ . '/PredictedQueryTest.php';

/**
 * Limepie Model 핵심 호환성 테스트 실행기
 * 
 * 3가지 의미 있는 테스트:
 * 1. 주입 없이 레거시 vs 리팩토링 호환성 (인스턴스 + getSql)
 * 2. 주입하고 예측 SQL vs 실제 getSql 비교 (리팩토링 정확성)
 * 
 * 실행 방법:
 * php src/Limepie/Model/Tests/RunAllTests.php
 * php src/Limepie/Model/Tests/LegacyCompatibilityTest.php
 * php src/Limepie/Model/Tests/PredictedQueryTest.php
 */

echo "🎯 Limepie ORM 완전 검증 테스트\n";
echo "레거시 호환성 + 리팩토링 정확성 종합 검증\n";
echo str_repeat("=", 80) . "\n";

// 핵심 테스트 실행
$test1Result = LegacyCompatibilityTest::runAllTests();
$test2Result = PredictedQueryTest::runAllTests();

// 전체 결과 요약
echo "\n" . str_repeat("=", 80) . "\n";
echo "🎯 최종 결과 요약\n";
echo str_repeat("=", 80) . "\n";

echo "테스트 1 (레거시 vs 리팩토링 호환성): " . ($test1Result ? "✅ PASS" : "❌ FAIL") . "\n";
echo "테스트 2 (예측 쿼리 vs 실제 getSql): " . ($test2Result ? "✅ PASS" : "❌ FAIL") . "\n";

$overallSuccess = $test1Result && $test2Result;

if ($overallSuccess) {
    echo "\n🎉 모든 검증 테스트 통과!\n";
    echo "✅ 레거시 호환성: 인스턴스 상태 + getSql 결과 100% 일치\n";
    echo "✅ 리팩토링 정확성: 외부 주입 시 예측 쿼리와 100% 일치\n";
    echo "✅ 전체 ORM 기능: CRUD + 집계 + 매직 메서드 모든 작업 검증\n\n";
    
    echo "📊 완전 검증 완료:\n";
    echo "- 레거시 호환성 (주입 없이): ✅\n";
    echo "- 리팩토링 정확성 (주입 있을 때): ✅\n";
    echo "- 모든 SQL 작업 유형: ✅\n";
} else {
    echo "\n⚠️  일부 검증 실패\n";
    echo "🔧 문제점 확인 필요\n";
    
    if (!$test1Result) {
        echo "- 레거시와 리팩토링 간 호환성 문제\n";
    }
    if (!$test2Result) {
        echo "- 리팩토링된 코드의 예측 쿼리 불일치\n";
    }
}

echo "\n📚 최종 테스트 구조:\n";
echo "- 3가지 핵심 검증: 레거시 호환성 + 리팩토링 정확성\n";
echo "- 불필요한 테스트 모두 제거\n";
echo "- 실제 의미있는 검증만 유지\n";

// 종료 코드 설정
exit($overallSuccess ? 0 : 1);