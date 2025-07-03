<?php declare(strict_types=1);

namespace Limepie;

/**
 * Level 클래스를 상속받은 테스트 및 분석 전용 클래스.
 *
 * 일일 1000포인트 기준의 다양한 레벨 시스템을 미리 정의하고
 * 테스트, 분석, 시뮬레이션 기능을 제공합니다.
 */
class LevelTester extends Level
{
    /**
     * 미리 정의된 레벨 시스템 타입.
     */
    public const SYSTEM_MAIN = 'main';           // 메인 추천 시스템

    public const SYSTEM_FAST = 'fast';           // 빠른 성장 시스템

    public const SYSTEM_HARDCORE = 'hardcore';   // 하드코어 시스템

    public const SYSTEM_CASUAL = 'casual';       // 캐주얼 시스템

    public const SYSTEM_EDUCATION = 'education'; // 교육용 시스템

    /**
     * 현재 시스템 타입.
     */
    private string $system_type;

    /**
     * 일일 기준 포인트 (기본값: 1000).
     */
    private int $daily_points;

    /**
     * LevelTester 생성자.
     *
     * @param string $system_type     시스템 타입 (기본값: 'main')
     * @param int    $daily_points    일일 기준 포인트 (기본값: 1000)
     * @param int    $max_cache_level 캐시 최대 레벨 (기본값: 300)
     */
    public function __construct(string $system_type = self::SYSTEM_MAIN, int $daily_points = 1000, int $max_cache_level = 300)
    {
        $this->system_type  = $system_type;
        $this->daily_points = $daily_points;

        $tiers = $this->get_predefined_tiers($system_type);
        parent::__construct($tiers, $max_cache_level);
    }

    /**
     * 미리 정의된 레벨 구간 반환.
     *
     * @param string $system_type 시스템 타입
     *
     * @return array 레벨 구간 배열
     */
    private function get_predefined_tiers(string $system_type) : array
    {
        return match ($system_type) {
            self::SYSTEM_MAIN => [
                [7, 1000],     // 첫 주: 하루 1레벨
                [14, 1500],    // 둘째 주: 1.5일 1레벨
                [25, 2000],    // 첫 달: 2일 1레벨
                [40, 3000],    // 2개월: 3일 1레벨
                [55, 4500],    // 3개월: 4.5일 1레벨
                [70, 6000],    // 6개월: 6일 1레벨
                [85, 8000],    // 9개월: 8일 1레벨
                [100, 12000],  // 1년: 12일 1레벨
                [PHP_INT_MAX, 20000],  // 이후: 20일 1레벨
            ],

            self::SYSTEM_FAST => [
                [10, 700],     // 튜토리얼: 0.7일 1레벨
                [25, 1200],    // 초급: 1.2일 1레벨
                [45, 2200],    // 중급: 2.2일 1레벨
                [70, 4000],    // 고급: 4일 1레벨
                [100, 8000],   // 마스터: 8일 1레벨
                [PHP_INT_MAX, 15000],  // 레전드: 15일 1레벨
            ],

            self::SYSTEM_HARDCORE => [
                [5, 1500],     // 기초: 1.5일 1레벨
                [15, 2500],    // 발전: 2.5일 1레벨
                [30, 4000],    // 숙련: 4일 1레벨
                [50, 6500],    // 전문: 6.5일 1레벨
                [75, 10000],   // 마스터: 10일 1레벨
                [100, 16000],  // 그랜드마스터: 16일 1레벨
                [PHP_INT_MAX, 25000],  // 레전드: 25일 1레벨
            ],

            self::SYSTEM_CASUAL => [
                [15, 500],     // 매우 빠른 시작
                [30, 800],     // 빠른 성장
                [50, 1500],    // 적당한 성장
                [80, 3000],    // 중간 성장
                [100, 5000],   // 후반 성장
                [PHP_INT_MAX, 10000],  // 엔드게임
            ],

            self::SYSTEM_EDUCATION => [
                [10, 300],     // 기초 학습
                [25, 500],     // 초급 학습
                [40, 800],     // 중급 학습
                [60, 1200],    // 고급 학습
                [80, 2000],    // 전문 학습
                [100, 3000],   // 마스터 학습
                [PHP_INT_MAX, 5000],  // 평생 학습
            ],

            default => throw new InvalidArgumentException("Unknown system type: {$system_type}")
        };
    }

    /**
     * 시스템 타입 조회.
     *
     * @return string 현재 시스템 타입
     */
    public function get_system_type() : string
    {
        return $this->system_type;
    }

    /**
     * 일일 기준 포인트 조회.
     *
     * @return int 일일 기준 포인트
     */
    public function get_daily_points() : int
    {
        return $this->daily_points;
    }

    /**
     * 시스템 상세 분석 실행.
     *
     * @param bool $show_output 출력 여부 (기본값: true)
     *
     * @return array 분석 결과 배열
     */
    public function analyze_system(bool $show_output = true) : array
    {
        $system_name = $this->get_system_display_name();

        if ($show_output) {
            echo "\n" . \str_repeat('=', 60) . "\n";
            echo "📊 {$system_name} - 일일 {$this->daily_points}포인트 기준 분석\n";
            echo \str_repeat('=', 60) . "\n";
        }

        // 기간별 달성 레벨 분석
        $period_analysis = $this->analyze_periods($show_output);

        // 주요 레벨별 소요 기간 분석
        $level_analysis = $this->analyze_major_levels($show_output);

        // 구간별 난이도 분석
        $difficulty_analysis = $this->analyze_difficulty($show_output);

        return [
            'system_type'         => $this->system_type,
            'system_name'         => $system_name,
            'daily_points'        => $this->daily_points,
            'period_analysis'     => $period_analysis,
            'level_analysis'      => $level_analysis,
            'difficulty_analysis' => $difficulty_analysis,
            'tier_info'           => $this->get_tier_info(),
        ];
    }

    /**
     * 기간별 달성 레벨 분석.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 분석 결과
     */
    private function analyze_periods(bool $show_output = true) : array
    {
        $periods = [
            ['1주일', 7],
            ['2주일', 14],
            ['1개월', 30],
            ['2개월', 60],
            ['3개월', 90],
            ['6개월', 180],
            ['9개월', 270],
            ['1년', 365],
            ['1년 6개월', 547],
            ['2년', 730],
        ];

        $results = [];

        if ($show_output) {
            echo "🎯 기간별 달성 레벨 (일일 {$this->daily_points}포인트 기준):\n";
            echo \str_repeat('-', 60) . "\n";
        }

        foreach ($periods as [$period_name, $days]) {
            $points       = $this->daily_points * $days;
            $level        = $this->points_to_level($points);
            $info         = $this->get_level_info($points);
            $progress     = $info['progress_percent'];
            $remaining    = $info['points_to_next'];
            $days_to_next = \ceil($remaining / $this->daily_points);

            $result = [
                'period'             => $period_name,
                'days'               => $days,
                'points'             => $points,
                'level'              => $level,
                'progress_percent'   => $progress,
                'days_to_next_level' => $days_to_next,
            ];

            $results[] = $result;

            if ($show_output) {
                echo \sprintf(
                    "%-12s: %2d레벨 (%5.1f%% 진행) - 다음 레벨까지 %d일\n",
                    $period_name,
                    $level,
                    $progress,
                    $days_to_next
                );
            }
        }

        return $results;
    }

    /**
     * 주요 레벨별 소요 기간 분석.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 분석 결과
     */
    private function analyze_major_levels(bool $show_output = true) : array
    {
        $target_levels = [10, 20, 30, 50, 70, 100, 150, 200];
        $results       = [];

        if ($show_output) {
            echo "\n🏆 주요 레벨 달성 소요 기간:\n";
            echo \str_repeat('-', 50) . "\n";
        }

        foreach ($target_levels as $target_level) {
            $total_points = $this->get_total_points_for_level($target_level);
            $days         = \ceil($total_points / $this->daily_points);
            $months       = \round($days / 30, 1);
            $years        = \round($days / 365, 1);

            $result = [
                'level'        => $target_level,
                'total_points' => $total_points,
                'days'         => $days,
                'months'       => $months,
                'years'        => $years,
            ];

            $results[] = $result;

            if ($show_output) {
                if ($days < 30) {
                    echo \sprintf(
                        "%3d레벨: %s포인트 (%d일)\n",
                        $target_level,
                        \number_format($total_points),
                        $days
                    );
                } elseif ($days < 365) {
                    echo \sprintf(
                        "%3d레벨: %s포인트 (%d일, %.1f개월)\n",
                        $target_level,
                        \number_format($total_points),
                        $days,
                        $months
                    );
                } else {
                    echo \sprintf(
                        "%3d레벨: %s포인트 (%d일, %.1f년)\n",
                        $target_level,
                        \number_format($total_points),
                        $days,
                        $years
                    );
                }
            }
        }

        return $results;
    }

    /**
     * 구간별 난이도 분석.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 분석 결과
     */
    private function analyze_difficulty(bool $show_output = true) : array
    {
        $check_levels = [5, 15, 25, 40, 55, 70, 85, 100];
        $results      = [];

        if ($show_output) {
            echo "\n⏱️ 구간별 1레벨 달성 시간:\n";
            echo \str_repeat('-', 50) . "\n";
        }

        foreach ($check_levels as $level) {
            $points_needed = $this->get_points_needed_for_next_level($level);
            $days          = \round($points_needed / $this->daily_points, 1);

            $result = [
                'from_level'    => $level,
                'to_level'      => $level + 1,
                'points_needed' => $points_needed,
                'days_needed'   => $days,
            ];

            $results[] = $result;

            if ($show_output) {
                echo \sprintf(
                    "%3d→%d레벨: %s포인트 (%s일)\n",
                    $level,
                    $level + 1,
                    \number_format($points_needed),
                    $days
                );
            }
        }

        return $results;
    }

    /**
     * 활동 패턴별 시뮬레이션.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 시뮬레이션 결과
     */
    public function simulate_activity_patterns(bool $show_output = true) : array
    {
        $patterns = [
            ['완벽한 사용자', $this->daily_points, 365],  // 매일
            ['주5일 사용자', $this->daily_points, 260],   // 평일만
            ['주3일 사용자', $this->daily_points, 156],   // 주 3일만
            ['불규칙 사용자', (int) ($this->daily_points * 0.7), 365], // 매일 하지만 적게
            ['주말 전사', $this->daily_points * 2, 104],  // 주말에만 몰아서
            ['월간 유저', $this->daily_points, 30],       // 한 달만
            ['극한 유저', $this->daily_points * 2, 365],   // 매일 2배
        ];

        $results = [];

        if ($show_output) {
            echo "\n" . \str_repeat('=', 60) . "\n";
            echo "🎮 {$this->get_system_display_name()} - 활동 패턴별 시뮬레이션\n";
            echo \str_repeat('=', 60) . "\n";
        }

        foreach ($patterns as [$name, $daily_points, $active_days]) {
            $total_points = $daily_points * $active_days;
            $level        = $this->points_to_level($total_points);
            $info         = $this->get_level_info($total_points);

            $result = [
                'pattern_name'     => $name,
                'daily_points'     => $daily_points,
                'active_days'      => $active_days,
                'total_points'     => $total_points,
                'final_level'      => $level,
                'progress_percent' => $info['progress_percent'],
            ];

            $results[] = $result;

            if ($show_output) {
                echo \sprintf(
                    "%-12s: %2d레벨 (총 %s포인트, %d일 활동)\n",
                    $name,
                    $level,
                    \number_format($total_points),
                    $active_days
                );
            }
        }

        return $results;
    }

    /**
     * 레벨업 보상 추천.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 보상 추천 결과
     */
    public function suggest_rewards(bool $show_output = true) : array
    {
        $milestone_levels = $this->get_milestone_levels();
        $results          = [];

        if ($show_output) {
            echo "\n" . \str_repeat('=', 60) . "\n";
            echo "🎁 레벨업 보상 추천 (일일 {$this->daily_points}포인트 기준)\n";
            echo \str_repeat('=', 60) . "\n";
        }

        foreach ($milestone_levels as [$level, $achievement, $reward_type, $reward_desc]) {
            $total_points = $this->get_total_points_for_level($level);
            $days         = \ceil($total_points / $this->daily_points);

            $result = [
                'level'              => $level,
                'achievement'        => $achievement,
                'reward_type'        => $reward_type,
                'reward_description' => $reward_desc,
                'required_points'    => $total_points,
                'required_days'      => $days,
            ];

            $results[] = $result;

            if ($show_output) {
                echo \sprintf(
                    "%3d레벨 (%3d일): %-15s → %s\n",
                    $level,
                    $days,
                    $achievement,
                    $reward_desc
                );
            }
        }

        return $results;
    }

    /**
     * 시스템별 마일스톤 레벨 정의.
     *
     * @return array 마일스톤 레벨 배열
     */
    private function get_milestone_levels() : array
    {
        return match ($this->system_type) {
            self::SYSTEM_MAIN => [
                [7, '1주일 달성', '기본', '작은 보상 (배지, 기본 아이템)'],
                [14, '2주일 달성', '중간', '중간 보상 (스킨, 기능 해제)'],
                [25, '1개월 달성', '월간', '월간 보상 (프리미엄 기능 체험)'],
                [40, '2개월 달성', '성장', '성장 보상 (능력치 업그레이드)'],
                [55, '3개월 달성', '계절', '계절 보상 (한정 아이템)'],
                [70, '6개월 달성', 'VIP', '중급자 보상 (VIP 혜택)'],
                [85, '9개월 달성', '고급', '고급자 보상 (독점 컨텐츠)'],
                [100, '1년 달성', '마스터', '마스터 보상 (최고급 혜택)'],
            ],

            self::SYSTEM_FAST => [
                [10, '1주일 달성', '기본', '빠른 성취 보상'],
                [25, '1개월 달성', '중간', '성장 가속 보상'],
                [45, '2개월 달성', '고급', '중급자 혜택'],
                [70, '6개월 달성', 'VIP', '고급 사용자 혜택'],
                [100, '1년 달성', '마스터', '최고급 혜택'],
            ],

            self::SYSTEM_HARDCORE => [
                [5, '1주일 달성', '기본', '첫 걸음 보상'],
                [15, '1개월 달성', '성장', '꾸준함 보상'],
                [30, '3개월 달성', '인내', '인내심 보상'],
                [50, '6개월 달성', '전문', '전문가 혜택'],
                [75, '1년 달성', '마스터', '마스터 혜택'],
                [100, '2년 달성', '레전드', '전설적 보상'],
            ],

            default => [
                [10, '초급 달성', '기본', '기본 보상'],
                [30, '중급 달성', '중간', '중간 보상'],
                [50, '고급 달성', '고급', '고급 보상'],
                [100, '마스터 달성', '마스터', '마스터 보상'],
            ]
        };
    }

    /**
     * 시스템 표시 이름 조회.
     *
     * @return string 시스템 표시 이름
     */
    private function get_system_display_name() : string
    {
        return match ($this->system_type) {
            self::SYSTEM_MAIN      => '메인 추천 시스템',
            self::SYSTEM_FAST      => '빠른 성장 시스템',
            self::SYSTEM_HARDCORE  => '하드코어 시스템',
            self::SYSTEM_CASUAL    => '캐주얼 시스템',
            self::SYSTEM_EDUCATION => '교육용 시스템',
            default                => '커스텀 시스템'
        };
    }

    /**
     * 특정 포인트의 상세 정보 출력.
     *
     * @param int  $points      포인트
     * @param bool $show_output 출력 여부
     *
     * @return array 상세 정보
     */
    public function analyze_specific_points(int $points, bool $show_output = true) : array
    {
        $info         = $this->get_level_info($points);
        $days_played  = \ceil($points / $this->daily_points);
        $days_to_next = \ceil($info['points_to_next'] / $this->daily_points);

        $result = [
            'points'             => $points,
            'days_played'        => $days_played,
            'level_info'         => $info,
            'days_to_next_level' => $days_to_next,
        ];

        if ($show_output) {
            echo "\n" . \str_repeat('=', 50) . "\n";
            echo '🎮 포인트 상세 분석: ' . \number_format($points) . "포인트\n";
            echo \str_repeat('=', 50) . "\n";
            echo "플레이 기간: {$days_played}일\n";
            echo "현재 레벨: {$info['current_level']}레벨\n";
            echo '레벨 진행도: ' . \number_format($info['level_progress']) . ' / '
                 . \number_format($info['points_needed_for_current_level']) . "\n";
            echo "진행률: {$info['progress_percent']}%\n";
            echo '다음 레벨까지: ' . \number_format($info['points_to_next']) . "포인트 ({$days_to_next}일)\n";
            echo "다음 레벨: {$info['next_level']}레벨\n";
        }

        return $result;
    }

    /**
     * 전체 테스트 실행.
     *
     * @param bool $show_output 출력 여부
     *
     * @return array 전체 테스트 결과
     */
    public function run_full_test(bool $show_output = true) : array
    {
        if ($show_output) {
            echo "\n" . \str_repeat('=', 80) . "\n";
            echo "🚀 {$this->get_system_display_name()} 전체 테스트 실행\n";
            echo \str_repeat('=', 80) . "\n";
        }

        $results = [
            'system_analysis'     => $this->analyze_system($show_output),
            'activity_simulation' => $this->simulate_activity_patterns($show_output),
            'reward_suggestions'  => $this->suggest_rewards($show_output),
            'sample_analysis'     => $this->analyze_specific_points(45000, $show_output),
            'cache_info'          => $this->get_cache_info(),
        ];

        if ($show_output) {
            echo "\n✨ 테스트 완료!\n";
        }

        return $results;
    }

    /**
     * 여러 시스템 비교 (정적 메서드).
     *
     * @param array $system_types 비교할 시스템 타입들
     * @param int   $daily_points 일일 포인트
     * @param bool  $show_output  출력 여부
     *
     * @return array 비교 결과
     */
    public static function compare_systems(array $system_types = ['main', 'fast', 'hardcore'], int $daily_points = 1000, bool $show_output = true) : array
    {
        $results = [];

        if ($show_output) {
            echo "\n" . \str_repeat('=', 80) . "\n";
            echo "📊 레벨 시스템 비교 분석 (일일 {$daily_points}포인트 기준)\n";
            echo \str_repeat('=', 80) . "\n";
        }

        foreach ($system_types as $system_type) {
            $tester                = new self($system_type, $daily_points);
            $analysis              = $tester->analyze_system($show_output);
            $results[$system_type] = $analysis;
        }

        return $results;
    }
}

// ==========================================
// 사용 예제
// ==========================================

// 메인 시스템 테스트
$main_tester  = new LevelTester(LevelTester::SYSTEM_MAIN, 1000);
$main_results = $main_tester->run_full_test();

// 빠른 성장 시스템 테스트
$fast_tester  = new LevelTester(LevelTester::SYSTEM_FAST, 1000);
$fast_results = $fast_tester->analyze_system();

// 하드코어 시스템 테스트
$hardcore_tester  = new LevelTester(LevelTester::SYSTEM_HARDCORE, 1000);
$hardcore_results = $hardcore_tester->simulate_activity_patterns();

// 여러 시스템 비교
$comparison = LevelTester::compare_systems(['main', 'fast', 'hardcore'], 1000);

// 특정 포인트 분석
$specific_analysis = $main_tester->analyze_specific_points(75000);

// 보상 시스템 확인
$rewards = $main_tester->suggest_rewards();

echo "\n🎯 LevelTester 클래스 사용 완료!\n";
echo "모든 테스트와 분석이 정상적으로 실행되었습니다.\n";
