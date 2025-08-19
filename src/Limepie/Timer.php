<?php declare(strict_types=1);

namespace Limepie;

class Timer
{
    public static $time = null;

    public static function start()
    {
        $time         = \explode(' ', \microtime());
        static::$time = (float) $time[1] + (float) $time[0];
    }

    public static function stop()
    {
        $time  = \explode(' ', \microtime());
        $time  = (float) $time[1] + (float) $time[0];
        $timer = static::$time;

        return \number_format($time - $timer, 6);
    }
}
