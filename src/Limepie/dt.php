<?php

declare(strict_types=1);

namespace Limepie;

class Dt
{
    /*
    echo get_time_ago('2023-11-13 10:00:00', 'ko'); // "3일 전"
    echo get_time_ago('2023-11-13 10:00:00', 'en'); // "3 days ago"
    echo get_time_ago('2023-11-13 10:00:00', 'ja'); // "3日前"
    echo get_time_ago('2023-11-13 10:00:00', 'zh'); // "3天前"
    echo get_time_ago('2023-11-13 10:00:00', 'uz'); // "3 kun oldin"
    */
    public static function get_time_ago($timestamp, $lang = 'ko')
    {
        $current_time    = \time();
        $time_difference = $current_time - \strtotime($timestamp);

        $language = [
            'ko' => [
                'just_now'  => '방금 전',
                'min_ago'   => '분 전',
                'hour_ago'  => '시간 전',
                'day_ago'   => '일 전',
                'month_ago' => '달 전',
                'year_ago'  => '년 전',
            ],
            'en' => [
                'just_now'  => 'just now',
                'min_ago'   => 'minutes ago',
                'hour_ago'  => 'hours ago',
                'day_ago'   => 'days ago',
                'month_ago' => 'months ago',
                'year_ago'  => 'years ago',
            ],
            'ja' => [
                'just_now'  => '先ほど',
                'min_ago'   => '分前',
                'hour_ago'  => '時間前',
                'day_ago'   => '日前',
                'month_ago' => 'ヶ月前',
                'year_ago'  => '年前',
            ],
            'zh' => [
                'just_now'  => '刚刚',
                'min_ago'   => '分钟前',
                'hour_ago'  => '小时前',
                'day_ago'   => '天前',
                'month_ago' => '个月前',
                'year_ago'  => '年前',
            ],
            'uz' => [
                'just_now'  => 'hozirgina',
                'min_ago'   => 'daqiqa oldin',
                'hour_ago'  => 'soat oldin',
                'day_ago'   => 'kun oldin',
                'month_ago' => 'oy oldin',
                'year_ago'  => 'yil oldin',
            ],
        ];

        // 언어가 없는 경우 한국어로 기본 설정
        if (!isset($language[$lang])) {
            $lang = 'ko';
        }

        $condition = [
            12 * 30 * 24 * 60 * 60 => function ($time) use ($language, $lang) {
                return \floor($time / (12 * 30 * 24 * 60 * 60)) . $language[$lang]['year_ago'];
            },
            30 * 24 * 60 * 60 => function ($time) use ($language, $lang) {
                return \floor($time / (30 * 24 * 60 * 60)) . $language[$lang]['month_ago'];
            },
            24 * 60 * 60 => function ($time) use ($language, $lang) {
                return \floor($time / (24 * 60 * 60)) . $language[$lang]['day_ago'];
            },
            60 * 60 => function ($time) use ($language, $lang) {
                return \floor($time / (60 * 60)) . $language[$lang]['hour_ago'];
            },
            60 => function ($time) use ($language, $lang) {
                return \floor($time / 60) . $language[$lang]['min_ago'];
            },
            0 => function ($time) use ($language, $lang) {
                return $language[$lang]['just_now'];
            },
        ];

        foreach ($condition as $secs => $callback) {
            if ($time_difference >= $secs) {
                return $callback($time_difference);
            }
        }
    }

    // Example usage:
    // echo formatRange('2024-11-02 10:00:00', '2024-12-05 18:00:00', 'ko');
    // 2024년 11월 2일 ~ 12월 5일
    // echo formatRange('2024-11-02 10:00:00', '2024-12-05 18:00:00', 'en');
    // 2024 11/02 ~ 12/05
    // echo formatRange('2024-11-02 10:00:00', '2024-12-05 18:00:00', 'ja');
    // 2024年 11月 2日 ~ 12月 5日
    // echo formatRange('2024-11-02 10:00:00', '2024-12-05 18:00:00', 'zh');
    // 2024年 11月 2日 ~ 12月 5日

    public static function format_range(?string $start_dt, ?string $end_dt, string $lang = 'ko') : string
    {
        if (null === $start_dt || null === $end_dt) {
            return '';
        }

        // Date formats by language
        $formats = [
            'ko' => [
                'full'       => '%s년 %s월 %s일 ~ %s년 %s월 %s일',
                'same_year'  => '%s년 %s월 %s일 ~ %s월 %s일',
                'same_month' => '%s년 %s월 %s일 ~ %s일',
            ],
            'en' => [
                'full'       => '%s/%s/%s ~ %s/%s/%s',
                'same_year'  => '%s %s/%s ~ %s/%s',
                'same_month' => '%s %s/%s ~ %s',
            ],
            'ja' => [
                'full'       => '%s年 %s月 %s日 ~ %s年 %s月 %s日',
                'same_year'  => '%s年 %s月 %s日 ~ %s月 %s日',
                'same_month' => '%s年 %s月 %s日 ~ %s日',
            ],
            'zh' => [
                'full'       => '%s年 %s月 %s日 ~ %s年 %s月 %s日',
                'same_year'  => '%s年 %s月 %s日 ~ %s月 %s日',
                'same_month' => '%s年 %s月 %s日 ~ %s日',
            ],
        ];

        // Use English format as fallback
        $format = $formats[$lang] ?? $formats['en'];

        $start = new \DateTime($start_dt);
        $end   = new \DateTime($end_dt);

        // Get components
        $startYear  = $start->format('Y');
        $startMonth = 'en' === $lang ? $start->format('m') : $start->format('n');
        $startDay   = 'en' === $lang ? $start->format('d') : $start->format('j');
        $endYear    = $end->format('Y');
        $endMonth   = 'en' === $lang ? $end->format('m') : $end->format('n');
        $endDay     = 'en' === $lang ? $end->format('d') : $end->format('j');

        // Different years
        if ($startYear !== $endYear) {
            return \sprintf(
                $format['full'],
                $startYear,
                $startMonth,
                $startDay,
                $endYear,
                $endMonth,
                $endDay
            );
        }

        // Same year, different months
        if ($startMonth !== $endMonth) {
            return \sprintf(
                $format['same_year'],
                $startYear,
                $startMonth,
                $startDay,
                $endMonth,
                $endDay
            );
        }

        // Same year and month
        return \sprintf(
            $format['same_month'],
            $startYear,
            $startMonth,
            $startDay,
            $endDay
        );
    }

    /**
     * Check if current time is between start and end timestamps.
     *
     * @param null|string $start_dt timestamp string
     * @param null|string $end_dt   timestamp string
     *
     * @return bool Returns true if current time is between start and end times
     */
    public static function inside(?string $start_dt, ?string $end_dt) : bool
    {
        // If either date is null, return false
        if (null === $start_dt || null === $end_dt) {
            return false;
        }

        // Get current timestamp
        $now = \time();

        // Convert MySQL timestamps to Unix timestamps
        $start = \strtotime($start_dt);
        $end   = \strtotime($end_dt);

        // Check if current time is between start and end times
        return $now >= $start && $now <= $end;
    }

    /*
    $testCases = [
        ['2023-12-01', '1D'],
        ['2023-11-20', '2W'],
        ['2023-10-15', '3M'],
        ['2022-12-01', '1Y'],
        [null, '1D'],
        ['', '1W'],
        ['2023-12-05', '1D']
    ];
    */
    public static function in_range($date, $range = '1D')
    {
        if (null === $date || '' === $date) {
            return false;
        }

        $now        = new \DateTime();
        $targetDate = new \DateTime($date);
        $interval   = new \DateInterval('P' . \substr($range, 0, -1) . \substr($range, -1));
        $rangeStart = (clone $now)->sub($interval);

        return $targetDate >= $rangeStart && $targetDate <= $now;
    }

    public static function get_relative_time($date, $lang = 'ko', $params = [])
    {
        $defaults = [
            'threshold_months' => 3,
            'date_format'      => 'Y-m-d',
        ];
        $params = \array_merge($defaults, $params);

        $time = \strtotime($date);
        $now  = \time();
        $diff = $now - $time;

        $intervals = [
            'year'   => 31536000,
            'month'  => 2592000,
            'week'   => 604800,
            'day'    => 86400,
            'hour'   => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        $translations = [
            'en' => [
                'year'     => 'year', 'years' => 'years',
                'month'    => 'month', 'months' => 'months',
                'week'     => 'week', 'weeks' => 'weeks',
                'day'      => 'day', 'days' => 'days',
                'hour'     => 'hour', 'hours' => 'hours',
                'minute'   => 'minute', 'minutes' => 'minutes',
                'second'   => 'second', 'seconds' => 'seconds',
                'ago'      => 'ago',
                'just_now' => 'just now',
            ],
            'ko' => [
                'year'     => '년', 'years' => '년',
                'month'    => '개월', 'months' => '개월',
                'week'     => '주', 'weeks' => '주',
                'day'      => '일', 'days' => '일',
                'hour'     => '시간', 'hours' => '시간',
                'minute'   => '분', 'minutes' => '분',
                'second'   => '초', 'seconds' => '초',
                'ago'      => '전',
                'just_now' => '방금',
            ],
            'ja' => [
                'year'     => '年', 'years' => '年',
                'month'    => 'ヶ月', 'months' => 'ヶ月',
                'week'     => '週間', 'weeks' => '週間',
                'day'      => '日', 'days' => '日',
                'hour'     => '時間', 'hours' => '時間',
                'minute'   => '分', 'minutes' => '分',
                'second'   => '秒', 'seconds' => '秒',
                'ago'      => '前',
                'just_now' => 'たった今',
            ],
        ];

        $t = $translations[$lang] ?? $translations['en'];

        foreach ($intervals as $interval => $seconds) {
            $count = \floor($diff / $seconds);

            if ($count > 0) {
                if ('month' == $interval && $count >= $params['threshold_months']) {
                    return \date($params['date_format'], $time);
                }
                $unit = 1 == $count ? $interval : $interval . 's';

                return 'ko' == $lang || 'ja' == $lang
                    ? "{$count}{$t[$unit]}{$t['ago']}"
                    : "{$count} {$t[$unit]} {$t['ago']}";
            }
        }

        return $t['just_now'];
    }

    public static function format(string $date, $format)
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

    public static function date(string $date)
    {
        // return 'aaa';
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

    public static function ago($enddate, $format = '$d day $H:$i:$s')
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

    public static function dday_count($targetDate, $is_zero_return = false)
    {
        $dDay = \ceil((\strtotime($targetDate) - \time()) / 86400);

        // D-Day가 0일일 경우 처리
        if (0 == $dDay) {
            return $is_zero_return ? '' : 0;
        }

        // D-Day가 양수 또는 음수일 경우 처리
        return \abs($dDay);
    }

    public static function period($start, $end, $after_today = false, $include_end_date = true)
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

    public static function beetween(\DateTime $startDate, \DateTime $endDate, \DateTime $subject)
    {
        return $subject->getTimestamp() >= $startDate->getTimestamp() && $subject->getTimestamp() <= $endDate->getTimestamp() ? true : false;
    }

    public static function diff_count(\DateTime $startDate, \DateTime $endDate)
    {
        return $startDate->diff($endDate)->format('%a') + 1;
    }

    public static function get_start_end($start, $end)
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

    public static function display_dday($startDate, $endDate, $keepTime = false)
    {
        // DateTime 객체로 변환
        $startDateTime = new \DateTime($startDate);
        $endDateTime   = new \DateTime($endDate);
        $now           = new \DateTime();

        // 시간 유지 여부에 따라 처리
        if (!$keepTime) {
            // 기존 방식: 시간 부분 제거
            $startDateTime->setTime(0, 0);
            $endDateTime->setTime(0, 0);
            $now->setTime(0, 0);
        }

        // 종료일이 이미 지난 경우
        if ($now > $endDateTime) {
            return null;
        }

        // 시작일이 아직 오지 않은 경우
        if ($now < $startDateTime) {
            $diff = $startDateTime->diff($now);

            return -$diff->days;
        }

        // 오늘이 모집 종료일인 경우 (시간 유지 모드에서는 정확한 시간 비교)
        if (!$keepTime && $now->format('Y-m-d') == $endDateTime->format('Y-m-d')) {
            return 0;
        }

        // 종료일까지 남은 일수 반환
        $diff = $endDateTime->diff($now);

        return $diff->days;
    }

    public static function display_dday_message($startDate, $endDate, $messages = [], $classes = [])
    {
        // **시간을 고려하여 display_dday 호출**
        $days = self::display_dday($startDate, $endDate, true);

        // 기본 메시지
        $defaultMessages = [
            'ko' => [
                'ended'           => '종료',
                'starts_in'       => '%d일 후 시작',
                'ends_today'      => '오늘 %s에 종료',
                'days_left'       => '%d일 남음',
                'starts_tomorrow' => '내일 %s에 시작',
                'ends_tomorrow'   => '내일 %s에 종료',
            ],
            'ja' => [
                'ended'           => '終了',
                'starts_in'       => '%d日後開始',
                'ends_today'      => '今日 %sに終了',
                'days_left'       => '%d日残り',
                'starts_tomorrow' => '明日 %sに開始',
                'ends_tomorrow'   => '明日 %sに終了',
            ],
            'en' => [
                'ended'           => 'Ended',
                'starts_in'       => '%d days later start',
                'ends_today'      => 'Today %s ends',
                'days_left'       => '%d days left',
                'starts_tomorrow' => 'Tomorrow %s starts',
                'ends_tomorrow'   => 'Tomorrow %s ends',
            ],
            'zh' => [
                'ended'           => '已结束',
                'starts_in'       => '%d天后开始',
                'ends_today'      => '今天 %s结束',
                'days_left'       => '%d天后结束',
                'starts_tomorrow' => '明天 %s开始',
                'ends_tomorrow'   => '明天 %s结束',
            ],
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

        $lang     = Di::getLanguageModel()->getId();
        $messages = \array_merge($defaultMessages[$lang], $messages);
        $classes  = \array_merge($defaultColors, $classes);

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

    public static function get_countdown_days($recruit_start_dt, $recruit_end_dt, $recruit_announce_dt, $end_dt, $days_before_end = 2, $lang = 'ko', $selected_messages = [])
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
                'en' => [
                    'today_end'   => 'Today ends',
                    'days_before' => 'D-%d',
                    'recruit_end' => 'Recruitment ends',
                    'announce'    => 'Announcement of winners',
                    'end'         => 'Ended',
                    'scheduled'   => 'Scheduled',
                    'ongoing'     => 'Ongoing',
                ],
                'zh' => [
                    'today_end'   => '今天结束',
                    'days_before' => 'D-%d',
                    'recruit_end' => '招募结束',
                    'announce'    => '获奖者公布',
                    'end'         => '结束',
                    'scheduled'   => '预定',
                    'ongoing'     => '进行中',
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
    public static function dday($endDate)
    {
        $currentDate = new \DateTime();
        $endDate     = new \DateTime($endDate);
        $interval    = $currentDate->diff($endDate);

        // 날짜 차이를 일수로 계산
        return (int) $interval->format('%r%a');
    }

    public static function getDayOfWeek($date)
    {
        // $date는 'YYYY-MM-DD' 형식의 문자열이어야 합니다.
        $timestamp = \strtotime($date);
        $dayOfWeek = \date('w', $timestamp); // 0 (일요일)에서 6 (토요일)까지의 정수 값을 반환합니다.

        $days = ['일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일'];

        return $days[$dayOfWeek];
    }

    public static function getTimeRemaining($date, $showHours = true, $showMinutes = true, $showSeconds = false)
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
    public static function getClosestNextEvent($baseDate, $intervalWeeks, $minDaysAfterToday = 7)
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
    public static function adjust($date, $days)
    {
        $dateTimestamp     = \strtotime($date);
        $adjustedTimestamp = \strtotime("{$days} days", $dateTimestamp);

        return \date('Y-m-d', $adjustedTimestamp);
    }

    public static function is_date_greater($input_date, $comparison_date = null)
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
    public static function calculateDiscountedPrice($baseDate, $currentPrice, $discountRates)
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

    public static function get_diff6($datetime1, $datetime2)
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

    public static function get_current6()
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

    public static function get_yoil($i)
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
    public static function time_ago($time, int $depth = 3) : string
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

    public static function day_ago($time) : string
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
    public static function iso8601micro(float $float) : string
    {
        $date = \DateTime::createFromFormat('U.u', (string) $float);
        $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

        return $date->format('Y-m-d\TH:i:s.uP');
    }
}
