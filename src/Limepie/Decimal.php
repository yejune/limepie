<?php declare(strict_types=1);

namespace Limepie;

class Decimal
{
    public static $scale = 3;

    public static function plus($a, $b) : string
    {
        return \bcadd(static::string($a), static::string($b), static::$scale);
    }

    public static function minus(string $a, string $b) : string
    {
        return \bcsub(static::string($a), static::string($b), static::$scale);
    }

    public static function multi($a, $b) : string
    {
        return \bcmul(static::string($a), static::string($b), static::$scale);
    }

    public static function div($a, $b) : string
    {
        return \bcdiv(static::string($a), static::string($b), static::$scale);
    }
    // < => -1, > => 1
    public static function compare($a, $b) : int
    {
        return \bccomp(static::string($a), static::string($b), static::$scale);
    }

    public static function equal($a, $b) : bool
    {
        return 0 === \bccomp(static::string($a), static::string($b), static::$scale);
    }

    public static function eq($a, $b) : bool
    {
        return static::equal($a, $b);
    }

    // little, <
    public static function lt($a, $b) : bool
    {
        return -1 === static::compare($a, $b);
    }

    // little or equal, <=
    public static function le($a, $b) : bool
    {
        return static::equal($a, $b) || -1 === static::compare($a, $b);
    }

    // greater, >
    public static function gt($a, $b) : bool
    {
        return 1 === static::compare($a, $b);
    }

    // greater or equal, >=
    public static function ge($a, $b) : bool
    {
        return static::equal($a, $b) || 1 === static::compare($a, $b);
    }

    public static function floor($number)
    {
        $number = static::string($number);
        if (false !== \strpos($number, '.')) {
            if (($tmp = \preg_replace('/\.0+$/', '', $number)) !== $number) {
                $number = $tmp;
            } elseif ('-' !== $number[0]) {
                $number = \bcadd($number, "0", 0);
            } else {
                $number = \bcsub($number, "1", 0);
            }
        }

        return '-0' === $number ? '0' : $number;
    }

    public static function ceil($number)
    {
        $number = static::string($number);
        if (false !== \strpos($number, '.')) {
            if (($tmp = \preg_replace('/\.0+$/', '', $number)) !== $number) {
                $number = $tmp;
            } elseif ('-' !== $number[0]) {
                $number = \bcadd($number, "1", 0);
            } else {
                $number = \bcsub($number, "0", 0);
            }
        }

        return '-0' === $number ? '0' : $number;
    }

    public static function round($number, $precision = 3)
    {
        $number = static::string($number);
        if (false !== \strpos($number, '.')) {
            if ('-' !== $number[0]) {
                $number = \bcadd($number, '0.' . \str_repeat('0', $precision) . '5', $precision);
            } else {
                $number = \bcsub($number, '0.' . \str_repeat('0', $precision) . '5', $precision);
            }
        } elseif (0 !== $precision) {
            $number .= '.' . \str_repeat('0', $precision);
        }
        // according to bccomp(), '-0.0' does not equal '-0'. However, '0.0' and '0' are equal.
        $zero = ('-' !== $number[0] ? 0 === \bccomp($number, '0') : 0 === \bccomp(\substr($number, 1), '0'));

        return $zero ? (0 === $precision ? '0' : '0.' . \str_repeat('0', $precision)) : $number;
    }

    public static function string($a) : string
    {
        if (false === \is_string($a)) {
            $a = (string) $a;
        }

        return \trim($a);
    }
}
