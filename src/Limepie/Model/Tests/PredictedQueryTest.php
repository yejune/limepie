<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoloader.php';

/**
 * 예측 쿼리 vs 리팩토링된 getSql 비교 테스트
 * 
 * 외부 주입을 사용했을 때 리팩토링된 코드가 
 * 예상한 쿼리와 바인딩을 정확히 생성하는지 검증
 */
class PredictedQueryTest
{
    private static int $testCount = 0;
    private static int $passedTests = 0;

    /**
     * 테스트 결과 출력 및 집계
     */
    private static function assert($condition, $message)
    {
        self::$testCount++;
        echo '[' . date('Y-m-d H:i:s') . '] ';
        
        if ($condition) {
            echo "✅ PASS: {$message}" . PHP_EOL;
            self::$passedTests++;
        } else {
            echo "❌ FAIL: {$message}" . PHP_EOL;
        }
    }

    /**
     * 예측 쿼리와 실제 getSql 결과 비교
     */
    private static function testPredictedQuery(callable $setupFunction, string $expectedSql, array $expectedBinds, string $operation, ?string $aggregateColumn = null, string $testName = '')
    {
        try {
            $model = new \Limepie\Model();
            $setupFunction($model);
            
            [$actualSql, $actualBinds] = $model->getSql($operation, $aggregateColumn);
            
            // SQL 정규화
            $expectedSqlNorm = self::normalizeSql($expectedSql);
            $actualSqlNorm = self::normalizeSql($actualSql);
            
            $sqlMatch = ($expectedSqlNorm === $actualSqlNorm);
            $bindsMatch = self::arraysEqual($expectedBinds, $actualBinds ?: []);
            
            $allMatch = $sqlMatch && $bindsMatch;
            
            // 성공한 경우에도 핵심 비교 내용 표시
            echo "--- {$testName} 검증 상세 ---" . PHP_EOL;
            echo "예측 SQL: [{$expectedSqlNorm}]" . PHP_EOL;
            echo "실제 SQL: [{$actualSqlNorm}]" . PHP_EOL;
            echo "SQL 일치: " . ($sqlMatch ? "✅" : "❌") . PHP_EOL;
            echo "예측 바인딩: " . json_encode($expectedBinds) . PHP_EOL;
            echo "실제 바인딩: " . json_encode($actualBinds ?: []) . PHP_EOL;
            echo "바인딩 일치: " . ($bindsMatch ? "✅" : "❌") . PHP_EOL;
            
            if (!$allMatch) {
                echo "⚠️ 예측과 실제 결과 불일치!" . PHP_EOL;
            } else {
                echo "🎯 예측 쿼리와 완벽히 일치!" . PHP_EOL;
            }
            echo PHP_EOL;
            
            self::assert($allMatch, "{$testName} - 예측 쿼리와 실제 결과 일치");
            
        } catch (\Exception $e) {
            self::assert(false, "{$testName} - 오류 발생: " . $e->getMessage());
        }
    }

    /**
     * 배열 비교
     */
    private static function arraysEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        
        foreach ($a as $key => $value) {
            if (!array_key_exists($key, $b) || $b[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * SQL 정규화
     */
    private static function normalizeSql(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    /**
     * 1. 매직 메서드 체이닝 테스트 (외부 주입 사용)
     */
    public static function testMagicMethodChaining()
    {
        self::testPredictedQuery(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';  
                $model->primaryKeyName = 'seq';
                
                // 실제 매직 메서드 사용 (외부 주입)
                $model->appendBindId('bind1')->conditionEqName('John')
                      ->appendBindId('bind2')->andGtAge(25);
            },
            "SELECT * FROM `users` AS `users` WHERE `users`.`name` = :name_bind1 AND `users`.`age` > :age_bind2",
            [':name_bind1' => 'John', ':age_bind2' => 25],
            'SELECT',
            null,
            '매직 메서드 체이닝 (외부 바인딩 주입)'
        );
    }

    /**
     * 2. 복합 조건 테스트 (IN, LIKE 조합)
     */
    public static function testComplexConditions()
    {
        self::testPredictedQuery(
            function($model) {
                $model->tableName = 'products';
                $model->tableAliasName = 'products';
                $model->primaryKeyName = 'seq';
                
                $model->appendBindId('bind1')->conditionInStatus(['active', 'featured'])
                      ->appendBindId('bind2')->andLikeTitle('%iPhone%');
            },
            "SELECT * FROM `products` AS `products` WHERE `products`.`status` IN (:status_bind1_0, :status_bind1_1) AND `products`.`title` LIKE :title_bind2",
            [':status_bind1_0' => 'active', ':status_bind1_1' => 'featured', ':title_bind2' => '%iPhone%'],
            'SELECT',
            null,
            '복합 조건 (IN + LIKE)'
        );
    }

    /**
     * 3. UPDATE 작업 (실제 실무 패턴: setter 체이닝 + 매직 메서드 WHERE)
     */
    public static function testUpdateWithMagicMethods()
    {
        self::testPredictedQuery(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';
                $model->primaryKeyName = 'seq';
                $model->allColumns = ['seq', 'name', 'age', 'status', 'updated_ts'];
                
                // 업데이트할 속성 설정 (실제 사용 패턴: setter 메서드 체이닝)
                $model->setName('Updated Name')->setStatus('verified');
                
                // WHERE 조건 (매직 메서드)
                $model->appendBindId('bind1')->conditionEqSeq(123);
            },
            "UPDATE `users` SET `users`.`name` = :name, `users`.`status` = :status WHERE `users`.`seq` = :seq_bind1",
            [':name' => 'Updated Name', ':status' => 'verified', ':seq_bind1' => 123],
            'UPDATE',
            null,
            'UPDATE 작업 (실무 패턴: setter 체이닝)'
        );
    }

    /**
     * 4. 집계 함수 (조건부 COUNT)
     */
    public static function testAggregateWithCondition()
    {
        self::testPredictedQuery(
            function($model) {
                $model->tableName = 'orders';
                $model->tableAliasName = 'orders';
                $model->primaryKeyName = 'seq';
                
                $model->appendBindId('bind1')->conditionEqStatus('completed')
                      ->appendBindId('bind2')->andGtTotalAmount(50000);
            },
            "SELECT COUNT(*) FROM `orders` AS `orders` WHERE `orders`.`status` = :status_bind1 AND `orders`.`total_amount` > :total_amount_bind2",
            [':status_bind1' => 'completed', ':total_amount_bind2' => 50000],
            'COUNT',
            null,
            '조건부 COUNT 집계'
        );
    }

    /**
     * 5. DELETE 작업 (복합 조건)
     */
    public static function testDeleteWithComplexCondition()
    {
        self::testPredictedQuery(
            function($model) {
                $model->tableName = 'temp_logs';
                $model->tableAliasName = 'temp_logs';
                $model->primaryKeyName = 'seq';
                
                $model->appendBindId('bind1')->conditionLtCreatedTs('2023-01-01 00:00:00')
                      ->appendBindId('bind2')->andEqProcessed(1);
            },
            "DELETE FROM `temp_logs` WHERE `temp_logs`.`created_ts` < :created_ts_bind1 AND `temp_logs`.`processed` = :processed_bind2",
            [':created_ts_bind1' => '2023-01-01 00:00:00', ':processed_bind2' => 1],
            'DELETE',
            null,
            'DELETE 작업 (복합 조건)'
        );
    }

    /**
     * 모든 테스트 실행
     */
    public static function runAllTests()
    {
        echo "=== 예측 쿼리 vs 실제 getSql 결과 비교 테스트 ===" . PHP_EOL;
        echo "외부 바인딩 주입을 사용했을 때의 쿼리 생성 정확성 검증" . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 테스트 시작' . PHP_EOL . PHP_EOL;

        self::testMagicMethodChaining();
        self::testComplexConditions();
        self::testUpdateWithMagicMethods();
        self::testAggregateWithCondition();
        self::testDeleteWithComplexCondition();

        echo PHP_EOL . "=== 테스트 결과 ===" . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 전체 테스트: ' . self::$testCount . '개' . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 성공한 테스트: ' . self::$passedTests . '개' . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 실패한 테스트: ' . (self::$testCount - self::$passedTests) . '개' . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 성공률: ' . round((self::$passedTests / self::$testCount) * 100, 1) . '%' . PHP_EOL;

        return self::$passedTests === self::$testCount;
    }
}

// 직접 실행시에만 테스트 수행
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $success = PredictedQueryTest::runAllTests();
    exit($success ? 0 : 1);
}