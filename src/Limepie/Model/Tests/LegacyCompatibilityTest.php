<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoloader.php';

/**
 * 레거시와 리팩토링된 코드의 호환성 테스트
 * 
 * 외부 주입 없이 동일한 조건을 설정했을 때 
 * getSql 결과와 인스턴스 상태가 동일한지 검증
 */
class LegacyCompatibilityTest
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
     * 레거시와 리팩토링된 모델의 호환성 테스트
     */
    private static function testCompatibility(callable $setupFunction, string $operation, ?string $aggregateColumn = null, string $testName = '')
    {
        try {
            // 레거시 모델 로드 (의존성 순서대로)
            require_once __DIR__ . '/../../../../legacy/ModelBase.php';
            require_once __DIR__ . '/../../../../legacy/ModelUtil.php';
            require_once __DIR__ . '/../../../../legacy/Model.php';
            
            // 레거시 모델 설정
            $legacyModel = new \Limepie\LegacyModel();
            $setupFunction($legacyModel);
            
            // 리팩토링된 모델 설정 (동일한 조건)
            $refactoredModel = new \Limepie\Model();
            $setupFunction($refactoredModel);
            
            // getSql 결과 비교
            [$legacySql, $legacyBinds] = $legacyModel->getSql($operation, $aggregateColumn);
            [$refactoredSql, $refactoredBinds] = $refactoredModel->getSql($operation, $aggregateColumn);
            
            // SQL 정규화
            $legacySqlNorm = self::normalizeSql($legacySql);
            $refactoredSqlNorm = self::normalizeSql($refactoredSql);
            
            // 비교
            $sqlMatch = ($legacySqlNorm === $refactoredSqlNorm);
            $bindsMatch = self::arraysEqual($legacyBinds ?: [], $refactoredBinds ?: []);
            
            // 인스턴스 상태 비교
            $instanceMatch = self::compareInstanceState($legacyModel, $refactoredModel);
            
            $allMatch = $sqlMatch && $bindsMatch && $instanceMatch;
            
            // 성공한 경우에도 핵심 비교 내용 표시
            echo "--- {$testName} 비교 상세 ---" . PHP_EOL;
            echo "레거시 SQL: [{$legacySqlNorm}]" . PHP_EOL;
            echo "리팩토링 SQL: [{$refactoredSqlNorm}]" . PHP_EOL;
            echo "SQL 일치: " . ($sqlMatch ? "✅" : "❌") . PHP_EOL;
            echo "레거시 바인딩: " . json_encode($legacyBinds ?: []) . PHP_EOL;
            echo "리팩토링 바인딩: " . json_encode($refactoredBinds ?: []) . PHP_EOL;
            echo "바인딩 일치: " . ($bindsMatch ? "✅" : "❌") . PHP_EOL;
            echo "인스턴스 상태 일치: " . ($instanceMatch ? "✅" : "❌") . PHP_EOL;
            
            if (!$allMatch) {
                echo "⚠️ 호환성 문제 발견!" . PHP_EOL;
            } else {
                echo "🎉 완벽한 호환성 확인!" . PHP_EOL;
            }
            echo PHP_EOL;
            
            self::assert($allMatch, "{$testName} - 레거시 vs 리팩토링 완전 호환성");
            
        } catch (\Exception $e) {
            self::assert(false, "{$testName} - 오류 발생: " . $e->getMessage());
        }
    }

    /**
     * 인스턴스 상태 비교
     */
    private static function compareInstanceState($legacyModel, $refactoredModel): bool
    {
        $properties = ['tableName', 'tableAliasName', 'primaryKeyName', 'condition', 'binds', 'attributes'];
        
        foreach ($properties as $property) {
            $legacyValue = $legacyModel->$property ?? null;
            $refactoredValue = $refactoredModel->$property ?? null;
            
            if (is_array($legacyValue) || is_array($refactoredValue)) {
                if (!self::arraysEqual($legacyValue ?: [], $refactoredValue ?: [])) {
                    return false;
                }
            } else {
                if ($legacyValue !== $refactoredValue) {
                    return false;
                }
            }
        }
        
        return true;
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
     * 1. 기본 SELECT 테스트 (매직 메서드 없이)
     */
    public static function testBasicSelect()
    {
        self::testCompatibility(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';
                $model->primaryKeyName = 'seq';
                // 외부 주입 없이 직접 조건 설정 (레거시와 동일하게)
                $model->condition = "`users`.`name` = :name";
                $model->binds = [':name' => 'John'];
            },
            'SELECT',
            null,
            '기본 SELECT (수동 조건 설정)'
        );
    }

    /**
     * 2. INSERT 테스트
     */
    public static function testInsert()
    {
        self::testCompatibility(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';
                $model->primaryKeyName = 'seq';
                $model->attributes = [
                    'name' => 'Alice',
                    'age' => 30,
                    'email' => 'alice@test.com'
                ];
            },
            'INSERT',
            null,
            'INSERT 작업'
        );
    }

    /**
     * 3. UPDATE 테스트
     */
    public static function testUpdate()
    {
        self::testCompatibility(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';
                $model->primaryKeyName = 'seq';
                $model->allColumns = ['seq', 'name', 'age', 'email', 'status'];
                $model->attributes = [
                    'name' => 'Bob',
                    'age' => 35
                ];
                $model->condition = "`users`.`seq` = :seq";
                $model->binds = [':seq' => 1];
            },
            'UPDATE',
            null,
            'UPDATE 작업'
        );
    }

    /**
     * 4. DELETE 테스트
     */
    public static function testDelete()
    {
        self::testCompatibility(
            function($model) {
                $model->tableName = 'users';
                $model->tableAliasName = 'users';
                $model->primaryKeyName = 'seq';
                $model->condition = "`users`.`seq` = :seq";
                $model->binds = [':seq' => 1];
            },
            'DELETE',
            null,
            'DELETE 작업'
        );
    }

    /**
     * 5. COUNT 집계 테스트
     */
    public static function testCount()
    {
        self::testCompatibility(
            function($model) {
                $model->tableName = 'products';
                $model->tableAliasName = 'products';
                $model->primaryKeyName = 'seq';
                $model->condition = "`products`.`price` > :price";
                $model->binds = [':price' => 1000];
            },
            'COUNT',
            null,
            'COUNT 집계'
        );
    }

    /**
     * 모든 테스트 실행
     */
    public static function runAllTests()
    {
        echo "=== 레거시 vs 리팩토링 호환성 테스트 (핵심 2가지) ===" . PHP_EOL;
        echo "1. getSql 결과 동일성 검증" . PHP_EOL;
        echo "2. 인스턴스 상태 동일성 검증" . PHP_EOL;
        echo '[' . date('Y-m-d H:i:s') . '] 테스트 시작' . PHP_EOL . PHP_EOL;

        self::testBasicSelect();
        self::testInsert();
        self::testUpdate();
        self::testDelete();
        self::testCount();

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
    $success = LegacyCompatibilityTest::runAllTests();
    exit($success ? 0 : 1);
}