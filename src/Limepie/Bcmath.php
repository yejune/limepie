<?php declare(strict_types=1);

namespace Limepie;

class Bcmath
{
    public static $scales = [
        'krw' => 0,
        'usd' => 2,
    ];

    public static $currency = 'krw';

    public static function setCurrency($currency)
    {
        static::$currency = $currency;
    }

    public static function getCurrency()
    {
        return static::$currency;
    }

    public static function getScale()
    {
        return static::$scales[static::$currency];
    }

    public static function add($a, $b) : string
    {
        return \bcadd((string) $a, (string) $b, static::getScale());
    }

    public static function sub($a, $b) : string
    {
        return \bcsub((string) $a, (string) $b, static::getScale());
    }

    public static function mul($a, $b) : string
    {
        return \bcmul((string) $a, (string) $b, static::getScale());
    }

    public static function div($a, $b) : string
    {
        return \bcdiv((string) $a, (string) $b, static::getScale());
    }

    public static function comp($a, $b) : int
    {
        return \bccomp((string) $a, (string) $b, static::getScale());
    }

    public static function lcomp($a, $b) : bool
    {
        return 1 === static::comp($a, $b);
    }

    public static function rcomp($a, $b) : bool
    {
        return -1 === static::comp($a, $b);
    }

    public static function equal($a, $b) : bool
    {
        return 0 === static::comp($a, $b);
    }

    public static function sum(array $array) : string
    {
        $sum = '0';

        foreach ($array as $row) {
            $sum = static::add($sum, $row);
        }

        return $sum;
    }

    public static function round($a) : string
    {
        return static::bcround((string) $a, static::getScale());
    }

    public static function ceil($a) : string
    {
        return static::bcceil((string) $a, static::getScale());
    }

    public static function floor($a) : string
    {
        return static::bcfloor((string) $a, static::getScale());
    }

    private static function bcceil($number)
    {
        if (false !== \strpos($number, '.')) {
            if (\preg_match("~\.[0]+$~", $number)) {
                return static::bcround($number, 0);
            }

            if ('-' !== $number[0]) {
                return \bcadd($number, 1, 0);
            }

            return \bcsub($number, 0, 0);
        }

        return $number;
    }

    private static function bcfloor($number)
    {
        if (false !== \strpos($number, '.')) {
            if (\preg_match("~\.[0]+$~", $number)) {
                return static::bcround($number, 0);
            }

            if ('-' !== $number[0]) {
                return \bcadd($number, 0, 0);
            }

            return \bcsub($number, 1, 0);
        }

        return $number;
    }

    private static function bcround($number, $precision = 0)
    {
        if (false !== \strpos($number, '.')) {
            if ('-' !== $number[0]) {
                return \bcadd($number, '0.' . \str_repeat('0', $precision) . '5', $precision);
            }

            return \bcsub($number, '0.' . \str_repeat('0', $precision) . '5', $precision);
        }

        return $number;
    }
}
