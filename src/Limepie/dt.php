<?php

declare(strict_types=1);

namespace Limepie\dt;

use Limepie\Exception;

function format(string $date, $format)
{
    $time = \strtotime($date);

    $week   = ['일', '월', '화', '수', '목', '금', '토'];
    $yoil   = $week[\date('w', $time)];
    $format = \str_replace('w', $yoil, $format);

    $week   = ['AM' => '오전', 'PM' => '오후'];
    $yoil   = $week[\date('A', $time)];
    $format = \str_replace('A', $yoil, $format);

    $hour = \date('H', $time);

    if ($hour > 12) {
        $hour -= 12;
    }
    $format = \str_replace('h', (string) $hour, $format);

    return (new \DateTime($date))->format($format);

    return \date($format, $time);
}

function date($date)
{
    $format = 'Y년 m월 d일 A h:i';
    $date   = \str_replace(['AM', 'PM'], ['오전', '오후'], \date($format, \strtotime($date)));

    if (false !== \stripos($date, '오전 12:00')) {
        $date = \str_replace('오전 12:00', '자정', $date);
    } elseif (false !== \stripos($date, '오전 12')) {
        $date = \str_replace('오전 12', '00', $date);
    } elseif (false !== \stripos($date, '오후 12:00')) {
        $date = \str_replace('오후 12:00', '정오', $date);
    } elseif (false !== \stripos($date, '오후 12')) {
        $date = \str_replace('오후 12', '낮 12', $date);
    }

    return $date;
}

function ago($enddate, $format = '$d day $H:$i:$s')
{
    $hour_bun = 60;
    $min_cho  = 60;
    $hour_cho = $min_cho  * $hour_bun;
    $il_cho   = $hour_cho * 24;

    if (true === \is_string($enddate)) {
        $enddate = \strtotime($enddate);
    }
    $timediffer = $enddate - \time();
    $day        = \floor($timediffer / $il_cho);
    $hour       = \floor(($timediffer - ($day * $il_cho)) / $hour_cho);
    $minute     = \floor(($timediffer - ($day * $il_cho) - ($hour * $hour_cho)) / $min_cho);
    $second     = $timediffer - ($day * $il_cho) - ($hour * $hour_cho) - ($minute * $min_cho);

    if (1 === \strlen((string) $minute)) {
        $minute = '0' . $minute;
    }

    if (1 === \strlen((string) $second)) {
        $second = '0' . $second;
    }

    return $day . '일하고, ' . $hour . ':' . $minute . ':' . $second . '';
}

function period($start, $end, $after_today = false, $include_end_date = true)
{
    if ($start instanceof \DateTime) {
        $first = $start;
    } else {
        $first = new \DateTime($start);
    }

    if ($end instanceof \DateTime) {
        $last = $end;
    } else {
        $last = new \DateTime($end);
    }

    if (true === $after_today) {
        $today = new \DateTime();

        if ($first < $today) {
            $first = $today;
        }
    }

    if ($include_end_date) {
        $enddate = (clone $last)->setTime(0, 0)->modify('+1 day'); // include end date
    } else {
        $enddate = $last->setTime(0, 0);
    }

    return new \DatePeriod(
        $first->setTime(0, 0),
        new \DateInterval('P1D'),
        $enddate
    );
}

function beetween(\DateTime $startDate, \DateTime $endDate, \DateTime $subject)
{
    return $subject->getTimestamp() >= $startDate->getTimestamp() && $subject->getTimestamp() <= $endDate->getTimestamp() ? true : false;
}

function diff_count(\DateTime $startDate, \DateTime $endDate)
{
    return $startDate->diff($endDate)->format('%a') + 1;
}

function get_start_end($start, $end)
{
    if (null === $start || null === $end) {
        return null;
    }
    $start = \strtotime($start);
    $end   = \strtotime($end);

    $startHour = ' ' . \date('H:i', $start) . '';
    $endtHour  = ' ' . \date('H:i', $end) . '';

    return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('Y-m-d', $end) . $endtHour;
    $startYear = \date('Y', $start);
    $endYear   = \date('Y', $end);

    if ($startYear == $endYear) {
        $startMonth = \date('m', $start);
        $endMonth   = \date('m', $end);

        if ($startMonth == $endMonth) {
            return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('d', $end) . $endtHour;
        }

        return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('m-d', $end) . $endtHour;
    }

    return \date('Y-m-d', $start) . $startHour . ' ~ ' . \date('Y-m-d', $end) . $endtHour;
}

function display_dday($startDate, $endDate)
{
    // 모집 시작일과 종료일을 DateTime 객체로 변환하고 시간 부분을 제거
    $startDate = new \DateTime($startDate);
    $startDate->setTime(0, 0);

    $endDate = new \DateTime($endDate);
    $endDate->setTime(0, 0);

    // 오늘 날짜 DateTime 객체로 가져오고 시간 부분을 제거
    $today = new \DateTime();
    $today->setTime(0, 0);

    // 종료일이 이미 지난 경우
    if ($today > $endDate) {
        return null;
    }

    // 모집 시작일까지 남은 일수 계산
    $daysUntilStart = $today->diff($startDate)->days;

    // 모집 종료일까지 남은 일수 계산
    $daysUntilEnd = $today->diff($endDate)->days;

    // 시작일이 아직 오지 않은 경우
    if ($today < $startDate) {
        return -$daysUntilStart;
    }

    // 오늘이 모집 종료일인 경우
    if ($today == $endDate) {
        return 0;
    }

    // 종료일까지 남은 일수 반환
    return $daysUntilEnd;
}

function display_dday_message($startDate, $endDate, $messages = [], $classes = [])
{
    // 내부 display_dday 함수 호출
    $days = display_dday($startDate, $endDate);

    // 기본 메시지
    $defaultMessages = [
        'ended'           => '종료',
        'starts_in'       => '%d일 후 시작',
        'ends_today'      => '오늘 %s에 종료',
        'days_left'       => '%d일 남음',
        'starts_tomorrow' => '내일 %s에 시작',
        'ends_tomorrow'   => '내일 %s에 종료',
    ];

    // 기본 클래스
    $defaultColors = [
        'ended'           => 'color-ended',
        'starts_in'       => 'color-starts-in',
        'ends_today'      => 'color-ends-today',
        'days_left'       => 'color-days-left',
        'starts_tomorrow' => 'color-starts-tomorrow',
        'ends_tomorrow'   => 'color-ends-tomorrow',
    ];

    // 사용자 정의 메시지가 있는 경우 기본 메시지를 덮어쓰기
    $messages = \array_merge($defaultMessages, $messages);

    // 사용자 정의 클래스가 있는 경우 기본 클래스를 덮어쓰기
    $classes = \array_merge($defaultColors, $classes);

    // DateTime 객체로 변환
    $startDateTime = new \DateTime($startDate);
    $endDateTime   = new \DateTime($endDate);

    // 시간 형식
    $startTime = $startDateTime->format('H:i');
    $endTime   = $endDateTime->format('H:i');

    // 메시지 작성
    if (\is_null($days)) {
        return \sprintf('<span class="%s">%s</span>', $classes['ended'], $messages['ended']);
    }

    if ($days < 0) {
        if (1 == \abs($days)) {
            return \sprintf('<span class="%s">%s</span>', $classes['starts_tomorrow'], \sprintf($messages['starts_tomorrow'], $startTime));
        }

        return \sprintf('<span class="%s">%s</span>', $classes['starts_in'], \sprintf($messages['starts_in'], \abs($days)));
    }

    if (0 == $days) {
        return \sprintf('<span class="%s">%s</span>', $classes['ends_today'], \sprintf($messages['ends_today'], $endTime));
    }

    if (1 == $days) {
        return \sprintf('<span class="%s">%s</span>', $classes['ends_tomorrow'], \sprintf($messages['ends_tomorrow'], $endTime));
    }

    return \sprintf('<span class="%s">%s</span>', $classes['days_left'], \sprintf($messages['days_left'], $days));
}

function get_countdown_days($recruit_start_dt, $recruit_end_dt, $recruit_announce_dt, $end_dt, $days_before_end = 2, $lang = 'ko', $selected_messages = [])
{
    $today                     = new \DateTime('now');
    $recruit_start             = new \DateTime($recruit_start_dt);
    $recruit_end               = new \DateTime($recruit_end_dt);
    $recruit_end_x_days_before = (clone $recruit_end)->modify("-{$days_before_end} days");
    $recruit_announce          = new \DateTime($recruit_announce_dt);
    $end                       = new \DateTime($end_dt);

    // 언어별로 메시지 매핑
    if ($selected_messages) {
    } else {
        $messages = [
            'ko' => [
                'today_end'   => '오늘 종료',
                'days_before' => 'D-%d',
                'recruit_end' => '모집 종료',
                'announce'    => '당첨자 발표',
                'end'         => '종료',
                'scheduled'   => '진행 예정',
                'ongoing'     => '모집 중',
            ],
            'ja' => [
                'today_end'   => '今日終了',
                'days_before' => 'D-%d',
                'recruit_end' => '募集終了',
                'announce'    => '当選者発表',
                'end'         => '終了',
                'scheduled'   => '予定',
                'ongoing'     => '募集中',
            ],
            // 다른 언어에 대한 메시지 추가 가능
        ];

        // 선택된 언어에 해당하는 메시지 배열 선택
        $selected_messages = $messages[$lang];
    }

    if ($today == $recruit_end) {
        return $selected_messages['today_end'];
    }

    if ($today == $recruit_end_x_days_before) {
        return \sprintf($selected_messages['days_before'], $days_before_end);
    }

    if ($today >= $recruit_end) {
        return $selected_messages['recruit_end'];
    }

    if ($today >= $recruit_announce && $today < $end) {
        return $selected_messages['announce'];
    }

    if ($today >= $end) {
        return $selected_messages['end'];
    }

    if ($today < $recruit_start) {
        return $selected_messages['scheduled'];
    }

    return $selected_messages['ongoing'];
}
// 주어진 종료 날짜와 현재 날짜 간의 차이를 일수로 계산하여 반환합니다.
// 남은 일수가 양수(+)로 표시되고 초과된 일수가 음수(-)로 표시
function dday($endDate)
{
    $currentDate = new \DateTime();
    $endDate     = new \DateTime($endDate);
    $interval    = $currentDate->diff($endDate);

    // 날짜 차이를 일수로 계산
    return (int) $interval->format('%r%a');
}

function getDayOfWeek($date)
{
    // $date는 'YYYY-MM-DD' 형식의 문자열이어야 합니다.
    $timestamp = \strtotime($date);
    $dayOfWeek = \date('w', $timestamp); // 0 (일요일)에서 6 (토요일)까지의 정수 값을 반환합니다.

    $days = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];

    return $days[$dayOfWeek];
}

function getTimeRemaining($date, $showHours = true, $showMinutes = true, $showSeconds = false)
{
    // $date는 'YYYY-MM-DD' 형식의 문자열이어야 합니다.
    $targetDate       = $date . ' 23:59:59';
    $targetTimestamp  = \strtotime($targetDate);
    $currentTimestamp = \time();

    $timeDifference = $targetTimestamp - $currentTimestamp;

    if ($timeDifference < 0) {
        return '날짜가 이미 지났습니다.';
    }

    $daysRemaining = \floor($timeDifference / 86400); // 1일은 86400초입니다.
    $timeDifference %= 86400;

    $hoursRemaining = \floor($timeDifference / 3600); // 1시간은 3600초입니다.
    $timeDifference %= 3600;

    $minutesRemaining = \floor($timeDifference / 60); // 1분은 60초입니다.
    $secondsRemaining = $timeDifference % 60;

    $result = "{$daysRemaining}일";

    if ($showHours) {
        $result .= " {$hoursRemaining}시간";
    }

    if ($showMinutes) {
        $result .= " {$minutesRemaining}분";
    }

    if ($showSeconds) {
        $result .= " {$secondsRemaining}초";
    }

    $result .= ' 남았습니다.';

    return $result;
}

// 기준일로부터 x주 간격으로 반복되는 일정의 가장 가까운 다음 일정을 계산
// 기준일이 미래 날짜인 경우 그 기준일 자체가 가장 가까운 다음 일정이 됨
// 최소 minDaysAfterToday일 이후 날짜를 반환하도록 설정
function getClosestNextEvent($baseDate, $intervalWeeks, $minDaysAfterToday = 7)
{
    if ($intervalWeeks < 1) {
        throw new \InvalidArgumentException('intervalWeeks는 1 이상의 정수여야 합니다.');
    }
    $baseDateTimestamp = \strtotime($baseDate);
    $currentTimestamp  = \time();
    $minDaysTimestamp  = \strtotime("+{$minDaysAfterToday} days", $currentTimestamp);

    if ($baseDateTimestamp >= $minDaysTimestamp) {
        return \date('Y-m-d', $baseDateTimestamp);
    }

    $nextEventTimestamp = $baseDateTimestamp;

    while ($nextEventTimestamp < $minDaysTimestamp) {
        $nextEventTimestamp = \strtotime("+{$intervalWeeks} weeks", $nextEventTimestamp);
    }

    return \date('Y-m-d', $nextEventTimestamp);
}

// 주어진 날짜에 일(day) 단위의 숫자를 더하거나 뺀 날짜를 반환
function adjust($date, $days)
{
    $dateTimestamp     = \strtotime($date);
    $adjustedTimestamp = \strtotime("{$days} days", $dateTimestamp);

    return \date('Y-m-d', $adjustedTimestamp);
}

function is_date_greater($input_date, $comparison_date = null)
{
    $timestamp_input = \strtotime($input_date);

    if (null === $comparison_date) {
        $timestamp_comparison = \time(); // 현재 Unix 타임스탬프를 가져옵니다.
    } else {
        $timestamp_comparison = \strtotime($comparison_date);
    }

    if ($timestamp_input > $timestamp_comparison) {
        return true;
    }

    return false;
}

/*

주어진 기준 날짜부터 현재까지의 월수에 따라 특정 가격에 할인을 적용합니다. 함수는 네 개의 매개변수를 받습니다:

$baseDate: 할인 계산의 기준이 되는 날짜입니다.
$currentPrice: 할인 전의 가격입니다.
$discountRates: 월별 할인율을 담은 연관 배열로, 각 월수에 해당하는 할인율을 정의합니다 (예: 1개월 이내 1.8% 할인).

함수는 기준 날짜로부터 현재까지의 월수를 계산하고, 이에 따라 해당 월에 적용되는 할인율을 찾아 가격에 적용합니다. 지정된 최대 월수를 초과하는 경우, 함수는 예외를 발생시켜 사용자에게 할인 기간이 초과되었음을 알립니다.


// 할인율 설정: [월 => 할인율(%)]
$discountRates = [
    1 => 1.8, // 1개월 이내
    2 => 1.2, // 2개월 이내
    3 => 0.6  // 3개월 이내
];

$maxMonths = 3; // 최대 할인 적용 가능 월

// 예시 사용
$baseDate = '2023-01-01'; // 기준 날짜 예시
$currentPrice = 10000; // 현재 가격 예시

try {
    $discountedPrice = calculateDiscountedPrice($baseDate, $currentPrice, $discountRates, $maxMonths);
    echo "할인된 가격: " . $discountedPrice;
} catch (Exception $e) {
    echo "오류: " . $e->getMessage();
}
 */
function calculateDiscountedPrice($baseDate, $currentPrice, $discountRates)
{
    // 할인율 배열이 비어있는 경우 확인
    if (empty($discountRates)) {
        return ['amount' => $currentPrice, 'discount' => 0, 'rate' => 1, 'month' => null];
    }

    $now      = new \DateTime();
    $baseDate = new \DateTime($baseDate);
    $interval = $now->diff($baseDate);

    $months = $interval->m + ($interval->y * 12); // 연도를 월로 변환

    // 할인 기간 초과 확인
    if ($months > \max(\array_keys($discountRates))) {
        throw new Exception('할인 기간이 초과되었습니다.');
    }

    // 할인율 적용
    foreach ($discountRates as $month => $rate) {
        if ($months <= $month) {
            $discount         = ($currentPrice * $rate) / 100;
            $discountedAmount = $currentPrice - $discount;

            return [
                'amount'   => \round($discountedAmount), // 반올림 적용
                'discount' => \round($discount), // 반올림 적용
                'month'    => $month,
                'rate'     => $rate,
            ];
        }
    }
}

function get_diff6($datetime1, $datetime2)
{
    if (null === $datetime1 || null === $datetime2) {
        return null;
    }
    $dt1 = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime1);
    $dt2 = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime2);

    if (!$dt1 || !$dt2) {
        return 'Invalid datetime format';
    }

    $interval     = $dt1->diff($dt2);
    $seconds      = $interval->s;
    $minutes      = $interval->i;
    $hours        = $interval->h;
    $days         = $interval->d;
    $months       = $interval->m;
    $years        = $interval->y;
    $microseconds = \abs($dt1->format('u') - $dt2->format('u'));

    $parts = [];

    if ($years > 0) {
        $parts[] = $years . ' years';
    }

    if ($months > 0) {
        $parts[] = $months . ' months';
    }

    if ($days > 0) {
        $parts[] = $days . ' days';
    }

    if ($hours > 0) {
        $parts[] = $hours . ' hours';
    }

    if ($minutes > 0) {
        $parts[] = $minutes . ' minutes';
    }

    if ($seconds > 0 || $microseconds > 0) {
        $parts[] = \sprintf('%d.%06d seconds', $seconds, $microseconds);
    }

    return \implode(', ', $parts);
}

function get_current6()
{
    // microtime을 float 형식으로 얻기
    $microtime = \microtime(true);

    // 전체 초와 마이크로초 부분을 분리
    $parts        = \explode('.', (string) $microtime);
    $seconds      = (int) $parts[0];
    $microseconds = isset($parts[1]) ? \str_pad((string) $parts[1], 6, '0') : '000000';

    // 날짜와 시간을 포맷팅
    return \date('Y-m-d H:i:s', $seconds) . '.' . $microseconds;
}

function get_yoil($i)
{
    // 월요일이 0부터 시작
    $week = ['월', '화', '수', '목', '금', '토', '일'];

    return $week[$i];
}

/**
 * time으로부터 지난 시간을 문자열로 반환.
 *
 * @param int|string $time  시간으로 표현가능한 문자열이나 숫자
 * @param int        $depth 표현 깊이
 */
function time_ago($time, int $depth = 3) : string
{
    if (true === \is_string($time)) {
        $time = \strtotime($time);
    }
    $time   = \time() - $time;
    $time   = (1 > $time) ? $time * -1 : $time;
    $tokens = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'min',
        1        => 'sec',
    ];
    $parts = [];

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = \floor($time / $unit);
        $parts[]       = $numberOfUnits . ' ' . $text . ((1 < $numberOfUnits) ? 's' : '');

        if (\count($parts) === $depth) {
            return \implode(' ', $parts);
        }
        $time -= ($unit * $numberOfUnits);
    }

    return \implode(' ', $parts);
}

function day_ago($time) : string
{
    if (true === \is_string($time)) {
        $time = \strtotime($time);
    }
    $time   = \time() - $time;
    $time   = (1 > $time) ? $time * -1 : $time;
    $tokens = [
        86400 => 'day',
    ];
    $parts = [];

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = \ceil($time / $unit);
        $parts[]       = $numberOfUnits; // . ' ' . $text . ((1 < $numberOfUnits) ? 's' : '');

        $time -= ($unit * $numberOfUnits);
    }

    return \implode(' ', $parts);
}

/**
 * formatting ISO8601MICROSENDS date.
 *
 * @param float $float microtime
 */
function iso8601micro(float $float) : string
{
    $date = \DateTime::createFromFormat('U.u', (string) $float);
    $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

    return $date->format('Y-m-d\TH:i:s.uP');
}
