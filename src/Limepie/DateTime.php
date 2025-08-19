<?php declare(strict_types=1);

namespace Limepie;

class DateTime extends \DateTime
{
    public const DOW_MONDAY = 1;

    public const DOW_TUESDAY = 2;

    public const DOW_WEDNESDAY = 3;

    public const DOW_THURSDAY = 4;

    public const DOW_FRIDAY = 5;

    public const DOW_SATURDAY = 6;

    public const DOW_SUNDAY = 7;

    public function weekofmonth($startDayOfWeek = self::DOW_SUNDAY)
    {
        $baseday   = $startDayOfWeek - 1;
        $baseday   = (0 >= $baseday) ? 7 : $baseday;
        $firstdate = $this->getDateOfFirstDayOfWeek($baseday);
        $remain    = $this->format('d') - $firstdate->format('d');

        if (0 >= $remain) {
            return 1;
        }

        return \ceil($remain / 7) + 1;
    }

    public function getDateOfFirstDayOfWeek($dayofweek)
    {
        $firstDay       = new \DateTime($this->format('Y-m-01'), $this->getTimezone());
        $onedayInterval = new \DateInterval('P1D');

        while ((int)$firstDay->format('N') !== $dayofweek) {
            $firstDay->add($onedayInterval);
        }

        return $firstDay;
    }
}
