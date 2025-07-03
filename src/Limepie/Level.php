<?php declare(strict_types=1);

namespace Limepie;

/**
 * 가중치 기반 레벨 시스템 클래스.
 *
 * 구간별로 다른 포인트 요구량을 설정할 수 있는 레벨 시스템
 * 캐싱 기능을 포함하여 높은 성능을 제공
 *
 * @example
 * $level = new Level();
 * echo $level->points_to_level(25000);  // 12
 *
 * // 커스텀 구간 설정
 * $custom_level = new Level([
 *     [5, 1000],    // 1~5레벨: 1000포인트씩
 *     [10, 2000],   // 6~10레벨: 2000포인트씩
 *     [PHP_INT_MAX, 5000]  // 11레벨 이상: 5000포인트씩
 * ]);
 */
class Level
{
    /**
     * 레벨 구간별 설정
     * [최대레벨, 구간당필요포인트] 형태의 배열.
     */
    private array $tiers;

    /**
     * 레벨별 누적 포인트 캐시
     * [레벨 => 누적포인트] 형태.
     */
    private array $level_cache = [];

    /**
     * 포인트별 레벨 캐시
     * [포인트범위 => 레벨] 형태로 역방향 캐시.
     */
    private array $points_cache = [];

    /**
     * 캐시 최대 레벨 (성능을 위해 제한).
     */
    private int $max_cache_level;

    /**
     * Level 클래스 생성자.
     *
     * @param null|array $custom_tiers    커스텀 구간 설정 (null이면 기본값 사용)
     * @param int        $max_cache_level 캐시할 최대 레벨 (기본값: 200)
     *
     * @example
     * // 기본 설정 사용
     * $level = new Level();
     *
     * // 커스텀 설정
     * $level = new Level([
     *     [10, 2000],   // 1~10레벨: 2000포인트씩
     *     [20, 4000],   // 11~20레벨: 4000포인트씩
     *     [PHP_INT_MAX, 8000]  // 21레벨 이상: 8000포인트씩
     * ]);
     */
    public function __construct(?array $custom_tiers = null, int $max_cache_level = 200)
    {
        $this->tiers           = $custom_tiers ?? $this->get_default_tiers();
        $this->max_cache_level = $max_cache_level;
        $this->init_cache();
    }

    /**
     * 기본 레벨 구간 설정.
     *
     * @return array 기본 구간 설정
     */
    private function get_default_tiers() : array
    {
        return [
            [10, 2000],   // 1~10레벨: 2000포인트씩
            [20, 3000],   // 11~20레벨: 3000포인트씩
            [30, 5000],   // 21~30레벨: 5000포인트씩
            [50, 8000],   // 31~50레벨: 8000포인트씩
            [PHP_INT_MAX, 15000],  // 51레벨 이상: 15000포인트씩
        ];
    }

    /**
     * 캐시 초기화
     * 성능 향상을 위해 자주 사용되는 레벨들의 누적 포인트를 미리 계산.
     */
    private function init_cache() : void
    {
        $this->level_cache = [1 => 0]; // 1레벨은 0포인트

        for ($level = 2; $level <= $this->max_cache_level; ++$level) {
            $this->level_cache[$level] = $this->calculate_total_points_for_level($level);
        }

        // 역방향 캐시도 생성 (포인트 -> 레벨 빠른 조회용)
        $this->build_points_cache();
    }

    /**
     * 포인트별 레벨 역방향 캐시 생성.
     */
    private function build_points_cache() : void
    {
        $this->points_cache = [];

        for ($level = 1; $level < $this->max_cache_level; ++$level) {
            $start_points = $this->level_cache[$level]     ?? 0;
            $end_points   = $this->level_cache[$level + 1] ?? PHP_INT_MAX;

            // 포인트 범위를 1000단위로 나누어 캐시 (메모리 절약)
            $start_key = (int) ($start_points / 1000);
            $end_key   = (int) ($end_points / 1000);

            for ($key = $start_key; $key < $end_key; ++$key) {
                $this->points_cache[$key] = $level;
            }
        }
    }

    /**
     * 특정 레벨까지 필요한 누적 포인트 계산 (캐시 없는 순수 계산).
     *
     * @param int $target_level 목표 레벨
     *
     * @return int 해당 레벨까지 필요한 총 포인트
     */
    private function calculate_total_points_for_level(int $target_level) : int
    {
        if ($target_level <= 1) {
            return 0;
        }

        $total_points  = 0;
        $current_level = 1;

        foreach ($this->tiers as [$max_level, $points_per_level]) {
            $tier_end_level = \min($max_level, $target_level - 1);

            if ($current_level <= $tier_end_level) {
                $levels_in_tier = $tier_end_level - $current_level + 1;
                $total_points += $levels_in_tier * $points_per_level;
                $current_level = $tier_end_level + 1;
            }

            if ($current_level >= $target_level) {
                break;
            }
        }

        return $total_points;
    }

    /**
     * 특정 레벨까지 필요한 누적 포인트 조회 (캐시 우선).
     *
     * @param int $target_level 목표 레벨
     *
     * @return int 해당 레벨까지 필요한 총 포인트
     */
    public function get_total_points_for_level(int $target_level) : int
    {
        if ($target_level <= 1) {
            return 0;
        }

        // 캐시에 있으면 캐시에서 반환
        if (isset($this->level_cache[$target_level])) {
            return $this->level_cache[$target_level];
        }

        // 캐시에 없으면 계산 후 캐시 저장 (합리적인 범위 내에서)
        $result = $this->calculate_total_points_for_level($target_level);

        if ($target_level <= $this->max_cache_level * 2) {
            $this->level_cache[$target_level] = $result;
        }

        return $result;
    }

    /**
     * 포인트를 레벨로 변환.
     *
     * @param int $points 현재 포인트
     *
     * @return int 현재 레벨
     *
     * @example
     * $level->points_to_level(25000);  // 12
     * $level->points_to_level(0);      // 1
     */
    public function points_to_level(int $points) : int
    {
        if ($points <= 0) {
            return 1;
        }

        // 빠른 조회를 위한 역방향 캐시 사용
        $cache_key = (int) ($points / 1000);

        if (isset($this->points_cache[$cache_key])) {
            $approximate_level = $this->points_cache[$cache_key];

            // 정확한 레벨 찾기 (근사값 주변에서만 검색)
            for ($level = \max(1, $approximate_level - 1); $level <= $approximate_level + 2; ++$level) {
                $current_points = $this->get_total_points_for_level($level);
                $next_points    = $this->get_total_points_for_level($level + 1);

                if ($points >= $current_points && $points < $next_points) {
                    return $level;
                }
            }
        }

        // 캐시 범위를 벗어나면 순차 검색
        $level = 1;

        while ($level <= $this->max_cache_level * 3) {
            $next_level_points = $this->get_total_points_for_level($level + 1);

            if ($points < $next_level_points) {
                return $level;
            }

            ++$level;
        }

        return $level;
    }

    /**
     * 현재 레벨에서 다음 레벨까지 필요한 포인트.
     *
     * @param int $points 현재 포인트
     *
     * @return int 다음 레벨까지 부족한 포인트
     *
     * @example
     * $level->points_to_next_level(25000);  // 3000
     */
    public function points_to_next_level(int $points) : int
    {
        $current_level     = $this->points_to_level($points);
        $next_level_points = $this->get_total_points_for_level($current_level + 1);

        return \max(0, $next_level_points - $points);
    }

    /**
     * 현재 레벨에서의 진행도 계산.
     *
     * @param int $points 현재 포인트
     *
     * @return int 현재 레벨에서의 진행 포인트
     *
     * @example
     * $level->get_level_progress(25000);  // 1000
     */
    public function get_level_progress(int $points) : int
    {
        $current_level       = $this->points_to_level($points);
        $current_level_start = $this->get_total_points_for_level($current_level);

        return \max(0, $points - $current_level_start);
    }

    /**
     * 현재 레벨 진행률 퍼센트 계산.
     *
     * @param int $points 현재 포인트
     *
     * @return float 진행률 (0.0 ~ 100.0)
     *
     * @example
     * $level->get_level_progress_percent(25000);  // 25.0
     */
    public function get_level_progress_percent(int $points) : float
    {
        $current_level       = $this->points_to_level($points);
        $current_level_start = $this->get_total_points_for_level($current_level);
        $next_level_start    = $this->get_total_points_for_level($current_level + 1);

        $level_range      = $next_level_start - $current_level_start;
        $current_progress = $points           - $current_level_start;

        if ($level_range <= 0) {
            return 0.0;
        }

        return \round(($current_progress / $level_range) * 100, 1);
    }

    /**
     * 특정 레벨에서 다음 레벨까지 필요한 포인트 조회.
     *
     * @param int $level 조회할 레벨
     *
     * @return int 해당 레벨에서 다음 레벨까지 필요한 포인트
     *
     * @example
     * $level->get_points_needed_for_next_level(10);  // 3000 (10->11레벨)
     */
    public function get_points_needed_for_next_level(int $level) : int
    {
        $current_start = $this->get_total_points_for_level($level);
        $next_start    = $this->get_total_points_for_level($level + 1);

        return $next_start - $current_start;
    }

    /**
     * 레벨 정보를 배열로 반환 (종합 정보).
     *
     * @param int $points 현재 포인트
     *
     * @return array 레벨 정보가 담긴 배열
     *
     * @example
     * $level->get_level_info(25000);
     * // 반환값: [
     * //   'current_level' => 12,
     * //   'current_points' => 25000,
     * //   'level_progress' => 1000,
     * //   'points_to_next' => 3000,
     * //   'progress_percent' => 25.0,
     * //   'next_level' => 13,
     * //   'next_level_start' => 28000,
     * //   'points_needed_for_current_level' => 4000
     * // ]
     */
    public function get_level_info(int $points) : array
    {
        $current_level             = $this->points_to_level($points);
        $level_progress            = $this->get_level_progress($points);
        $points_to_next            = $this->points_to_next_level($points);
        $progress_percent          = $this->get_level_progress_percent($points);
        $next_level_start          = $this->get_total_points_for_level($current_level + 1);
        $points_needed_for_current = $this->get_points_needed_for_next_level($current_level);

        return [
            'current_level'                   => $current_level,
            'current_points'                  => $points,
            'level_progress'                  => $level_progress,
            'points_to_next'                  => $points_to_next,
            'progress_percent'                => $progress_percent,
            'next_level'                      => $current_level + 1,
            'next_level_start'                => $next_level_start,
            'points_needed_for_current_level' => $points_needed_for_current,
        ];
    }

    /**
     * 레벨 구간 정보 조회.
     *
     * @return array 현재 설정된 레벨 구간 정보
     */
    public function get_tiers() : array
    {
        return $this->tiers;
    }

    /**
     * 레벨 구간 정보를 사람이 읽기 쉬운 형태로 반환.
     *
     * @return array 레벨 구간별 상세 정보
     *
     * @example
     * $level->get_tier_info();
     * // 반환값: [
     * //   ['range' => '1-10', 'points_per_level' => 2000, 'total_points' => 18000],
     * //   ['range' => '11-20', 'points_per_level' => 3000, 'total_points' => 30000],
     * //   ...
     * // ]
     */
    public function get_tier_info() : array
    {
        $result      = [];
        $start_level = 1;

        foreach ($this->tiers as [$max_level, $points_per_level]) {
            $end_level            = PHP_INT_MAX === $max_level ? '∞' : $max_level;
            $levels_in_tier       = PHP_INT_MAX === $max_level ? '∞' : ($max_level - $start_level + 1);
            $total_points_in_tier = PHP_INT_MAX === $max_level ? '∞' : ($levels_in_tier * $points_per_level);

            $result[] = [
                'range'                => $start_level . '-' . $end_level,
                'points_per_level'     => $points_per_level,
                'total_points_in_tier' => $total_points_in_tier,
                'levels_in_tier'       => $levels_in_tier,
            ];

            $start_level = $max_level + 1;

            if (PHP_INT_MAX === $max_level) {
                break;
            }
        }

        return $result;
    }

    /**
     * 캐시 상태 정보 조회 (디버깅/최적화 용도).
     *
     * @return array 캐시 통계 정보
     */
    public function get_cache_info() : array
    {
        return [
            'level_cache_size'      => \count($this->level_cache),
            'points_cache_size'     => \count($this->points_cache),
            'max_cache_level'       => $this->max_cache_level,
            'memory_usage_estimate' => (\count($this->level_cache) + \count($this->points_cache)) * 8 . ' bytes',
        ];
    }
}

// =====================================================
// 사용 예제
// =====================================================

/*
// 기본 사용법
$level = new Level();

echo "=== 기본 레벨 시스템 ===\n";
echo "25000포인트: " . $level->points_to_level(25000) . "레벨\n";
echo "다음 레벨까지: " . $level->points_to_next_level(25000) . "포인트\n";

// 상세 정보
$info = $level->get_level_info(25000);
print_r($info);

// 커스텀 구간 설정
$custom_level = new Level([
    [5, 1000],    // 1~5레벨: 1000포인트씩
    [10, 2000],   // 6~10레벨: 2000포인트씩
    [PHP_INT_MAX, 5000]  // 11레벨 이상: 5000포인트씩
]);

echo "\n=== 커스텀 레벨 시스템 ===\n";
echo "5000포인트: " . $custom_level->points_to_level(5000) . "레벨\n";

// 구간 정보 확인
echo "\n=== 구간 정보 ===\n";
print_r($level->get_tier_info());
*/
