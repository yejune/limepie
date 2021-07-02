<?php declare(strict_types=1);

namespace Limepie\Calendar;

class Month
{
    public $days = [];

    public $start;

    public $end;

    public $holidays = [];

    public function setHolidays(array $holidays = [])
    {
        $this->holidays = $holidays;
    }

    // 달력에 들어갈 한달치 날짜정보를 만들어 리턴한다.
    public function getTable(\DateTime $start, \DateTime $end)
    {
        $this->days = [];
        $first      = (clone $start)->modify('first day of this month');
        $last       = (clone $end)->modify('last day of this month');

        $period = new \DatePeriod(
            $first,
            new \DateInterval('P1D'),
            (clone $last)->modify('+1 day') // include end date
        );

        // (1) 달력 앞쪽에 빈 셀을 채우기 위해 지난 달 날짜들을 넣는다.

        if (0 !== (int) $first->format('w')) {
            // 보여줄 달의 이전 달 정보를 가져온다.

            $prev = (clone $first)->modify('- ' . $first->format('w') . ' days');

            $prependPeriod = new \DatePeriod(
                $prev,
                new \DateInterval('P1D'),
                $first
            );

            foreach ($prependPeriod as $key => $value) {
                $holiday      = $this->holidays[$value->format('Y-m-d')] ?? ['name' => null, 'is_holiday' => 0];
                $this->days[] = [
                    'name'        => $holiday['name'],
                    'datetime'    => $value,
                    'is_holiday'  => $holiday['is_holiday'],
                    'is_blank'    => 1,
                    'is_current'  => (int)( $value >= $this->start && $value <= $this->end),
                    'yoil_number' => $value->format('w'),
                    'day_number'  => $value->format('j')
                ];
            }
        }

        foreach ($period as $key => $value) {
            $holiday = $this->holidays[$value->format('Y-m-d')] ?? ['name' => null, 'is_holiday' => 0];
            //\pr($value, $start, $value >= $start);
            $this->days[] = [
                'name'        => $holiday['name'],
                'datetime'    => $value,
                'is_holiday'  => $holiday['is_holiday'],
                'is_blank'    => 0,
                'is_current'  => (int) ($value >= $this->start && $value <= $this->end),
                'yoil_number' => $value->format('w'),
                'day_number'  => $value->format('j')
            ];
        }

        // (3) 빈 셀을 채우기 위한 다음 달 날짜를 넣는다.

        if (6 !== (int) $last->format('w')) {
            // 보여줄 달의 이전 달 정보를 가져온다.
            $appendPeriod = new \DatePeriod(
                (clone $last)->modify('+ 1 days'),
                new \DateInterval('P1D'),
                (clone $last)->modify('+ ' . ((6 - $last->format('w')) + 1) . ' days')
            );

            foreach ($appendPeriod as $key => $value) {
                $holiday      = $this->holidays[$value->format('Y-m-d')] ?? ['name' => null, 'is_holiday' => 0];
                $this->days[] = [
                    'name'        => $holiday['name'],
                    'datetime'    => $value,
                    'is_holiday'  => $holiday['is_holiday'],
                    'is_blank'    => 1,
                    'is_current'  => (int)($value >= $this->start && $value <= $this->end),
                    'yoil_number' => $value->format('w'),
                    'day_number'  => $value->format('j')
                ];
            }
        }

        // $this->days 배열요소들을 7개씩 묶은 다중배열을 만들어 리턴한다.
        return \array_chunk($this->days, 7);
    }

    public function getTable2(\DateTime $start, \DateTime $end)
    {
        $this->start = $start;
        $this->end   = $end;

        $period0 = new \DatePeriod(
            $start,
            new \DateInterval('P1M'),
            (clone $end)->modify('+1 day') // include end date
        );

        $group = [];

        foreach ($period0 as $current) {
            $group[$current->format('Y-m')] = $this->getTable($current, $current);
        }
        // $this->days 배열요소들을 7개씩 묶은 다중배열을 만들어 리턴한다.
        return $group;
    }
}
