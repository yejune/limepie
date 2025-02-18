<?php

namespace Limepie;

class Display
{
    // 상태 상수
    public const STATUS_ALWAYS_SHOW = 'ALWAYS_SHOW';

    public const STATUS_WAITING = 'WAITING';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_DAY_ACTIVE = 'DAY_ACTIVE';

    public const STATUS_DAY_RESTRICTED = 'DAY_RESTRICTED';

    public const STATUS_HIDDEN = 'HIDDEN';

    private static $statusInfo = [
        self::STATUS_ALWAYS_SHOW => [
            'message' => '항상표시',
            'class'   => 'display-status-active',
        ],
        self::STATUS_WAITING => [
            'message' => '예정',
            'class'   => 'display-status-waiting',
        ],
        self::STATUS_EXPIRED => [
            'message' => '지남',
            'class'   => 'display-status-expired',
        ],
        self::STATUS_ACTIVE => [
            'message' => '기간표시',
            'class'   => 'display-status-active',
        ],
        self::STATUS_DAY_ACTIVE => [
            'message' => '요일표시',
            'class'   => 'display-status-day-active',
        ],
        self::STATUS_DAY_RESTRICTED => [
            'message' => '요일제한',
            'class'   => 'display-status-restricted',
        ],
        self::STATUS_HIDDEN => [
            'message' => '노출안함',
            'class'   => 'display-status-hidden',
        ],
    ];

    private static function calculateStatus($row)
    {
        $now    = \date('Y-m-d H:i:s');
        $status = [
            'isDisplay' => false,
            'isExpired' => false,
            'isWaiting' => false,
            'status'    => '',
            'message'   => '',
            'class'     => '',
        ];

        // is_display가 0인 경우 (노출 안함)
        if (0 == $row['is_display']) {
            $status['status']  = self::STATUS_HIDDEN;
            $status['message'] = self::$statusInfo[self::STATUS_HIDDEN]['message'];
            $status['class']   = self::$statusInfo[self::STATUS_HIDDEN]['class'];

            return $status;
        }

        // is_display가 1인 경우 (항상 표시)
        if (1 == $row['is_display']) {
            $status['isDisplay'] = true;
            $status['status']    = self::STATUS_ALWAYS_SHOW;
            $status['message']   = self::$statusInfo[self::STATUS_ALWAYS_SHOW]['message'];
            $status['class']     = self::$statusInfo[self::STATUS_ALWAYS_SHOW]['class'];

            return $status;
        }

        // is_display가 2 또는 3인 경우
        if (2 == $row['is_display'] || 3 == $row['is_display']) {
            // 시작일이 미래인 경우
            if ($row['display_start_dt'] > $now) {
                $status['isWaiting'] = true;
                $status['status']    = self::STATUS_WAITING;
                $status['message']   = self::$statusInfo[self::STATUS_WAITING]['message'];
                $status['class']     = self::$statusInfo[self::STATUS_WAITING]['class'];

                return $status;
            }

            // is_display가 2이고 종료일이 과거인 경우
            if (2 == $row['is_display'] && $row['display_end_dt'] < $now) {
                $status['isExpired'] = true;
                $status['status']    = self::STATUS_EXPIRED;
                $status['message']   = self::$statusInfo[self::STATUS_EXPIRED]['message'];
                $status['class']     = self::$statusInfo[self::STATUS_EXPIRED]['class'];

                return $status;
            }

            // 날짜가 유효한 경우
            $isDateValid = false;

            if (2 == $row['is_display']) {
                if ($row['display_start_dt'] <= $now
                    && $row['display_end_dt'] >= $now) {
                    $isDateValid = true;
                }
            } else { // is_display == 3
                if ($row['display_start_dt'] <= $now) {
                    $isDateValid = true;
                }
            }

            if ($isDateValid) {
                // 요일 제한이 없는 경우
                if (0 == $row['is_allday']) {
                    $status['isDisplay'] = true;
                    $status['status']    = self::STATUS_ACTIVE;
                    $status['message']   = self::$statusInfo[self::STATUS_ACTIVE]['message'];
                    $status['class']     = self::$statusInfo[self::STATUS_ACTIVE]['class'];

                    return $status;
                }

                // 요일 체크
                $currentDayOfWeek = \date('w', \strtotime($now));
                $dayMap           = [
                    0 => 'is_sunday',
                    1 => 'is_monday',
                    2 => 'is_tuesday',
                    3 => 'is_wednesday',
                    4 => 'is_thursday',
                    5 => 'is_friday',
                    6 => 'is_saturday',
                ];

                if (isset($dayMap[$currentDayOfWeek])
                    && 1 == $row[$dayMap[$currentDayOfWeek]]) {
                    $status['isDisplay'] = true;
                    $status['status']    = self::STATUS_DAY_ACTIVE;
                    $status['message']   = self::$statusInfo[self::STATUS_DAY_ACTIVE]['message'];
                    $status['class']     = self::$statusInfo[self::STATUS_DAY_ACTIVE]['class'];
                } else {
                    $status['status']  = self::STATUS_DAY_RESTRICTED;
                    $status['message'] = self::$statusInfo[self::STATUS_DAY_RESTRICTED]['message'];
                    $status['class']   = self::$statusInfo[self::STATUS_DAY_RESTRICTED]['class'];
                }
            }
        }

        return $status;
    }

    private static function getDisplayInfo($row, $isDateShort = false)
    {
        if (0 == $row['is_display']) {
            return [
                'date' => 'OFF',
            ];
        }

        if (1 == $row['is_display']) {
            return [
                //  'date' => '항상표시',
            ];
        }

        $dateFormat = $isDateShort ? 'Y-m-d H:i' : 'Y-m-d H:i';
        $info       = [];

        $startDate = \date($dateFormat, \strtotime($row['display_start_dt']));

        if (2 == $row['is_display']) {
            $endDate      = \date($dateFormat, \strtotime($row['display_end_dt']));
            $info['date'] = $startDate . ' ~ ' . PHP_EOL . $endDate;
        } else {
            $info['date'] = $startDate . ' ~';
        }

        // 짧은 날짜 형식이고 현재 연도와 같은 경우 연도 제외
        if ($isDateShort) {
            $info['date'] = \str_replace(\date('Y') . '-', '', $info['date']);
        }

        // 요일 제한이 있는 경우
        if (1 == $row['is_allday']) {
            $days     = [];
            $dayNames = [
                'is_sunday'    => '일',
                'is_monday'    => '월',
                'is_tuesday'   => '화',
                'is_wednesday' => '수',
                'is_thursday'  => '목',
                'is_friday'    => '금',
                'is_saturday'  => '토',
            ];

            foreach ($dayNames as $key => $name) {
                if (1 == $row[$key]) {
                    $days[] = $name;
                }
            }

            if ($days) {
                $info['days'] = PHP_EOL . \implode(',', $days) . '요일만';
            }
        }

        return $info;
    }

    public static function getHtml($row)
    {
        $status = self::calculateStatus($row);
        $info   = self::getDisplayInfo($row);

        if ($info) {
            $title = $info['date'];

            if (isset($info['days'])) {
                $title .= $info['days'];
            }

            $popoverAttr = ' style="cursor: pointer;" data-bs-html="true" data-bs-toggle="popover" '
                        . 'data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="노출 조건" '
                        . 'data-bs-content="' . \str_replace("\n", '<br />', $title) . '"';
        } else {
            $popoverAttr = '';
        }

        return '<span class="' . $status['class'] . '"' . $popoverAttr . '>'
             . \htmlspecialchars($status['message'])
             . '</span>';
    }

    public static function getPopover($row)
    {
        $status = self::calculateStatus($row);
        $info   = self::getDisplayInfo($row);

        if ($info) {
            $title = $info['date'];

            if (isset($info['days'])) {
                $title .= $info['days'];
            }

            $popoverAttr = ' style="cursor: pointer;" data-bs-html="true" data-bs-toggle="popover" '
                        . 'data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="노출 조건" '
                        . 'data-bs-content="' . \str_replace("\n", '<br />', $title) . '"';
        } else {
            $popoverAttr = '';
        }

        return '<span class="' . $status['class'] . '"' . $popoverAttr . '>'
             . \Limepie\append_string($status['message'], ': ', self::getTitle($row, true))
             . '</span>';
    }

    public static function getTitle($row, $isDateShort = false)
    {
        $info = self::getDisplayInfo($row, $isDateShort);

        if (!$info) {
            return '';
        }

        $title = [$info['date']];

        if (isset($info['days'])) {
            $title[] = '(' . \trim($info['days']) . ')';
        }

        return \implode(' ', $title);
    }

    public static function getStatus($row)
    {
        return self::calculateStatus($row)['status'];
    }

    public static function getMessage($row)
    {
        return self::calculateStatus($row)['message'];
    }

    public static function getClass($row)
    {
        return self::calculateStatus($row)['class'];
    }

    public static function isDisplay($row)
    {
        return self::calculateStatus($row)['isDisplay'];
    }

    public static function isExpired($row)
    {
        return self::calculateStatus($row)['isExpired'];
    }

    public static function isWaiting($row)
    {
        return self::calculateStatus($row)['isWaiting'];
    }

    public static function get($row)
    {
        return self::calculateStatus($row);
    }
}
