<?php declare(strict_types=1);

namespace Limepie\Router;

class Rule
{
    public static $keys = [
        'module',
        'namespace',
        'controller',
        // 'sub_controller',
        // 'sub_sub_controller',
        'action',
        'path',
        'next'
    ];

    public static function getMatched(string $pattern, string $subject) : array
    {
        $matches = [];

        if (1 === \preg_match($pattern, $subject, $matches)) {
            if ($matches) {
                $returns               = [];
                $returns['properties'] = [];

                foreach ($matches as $key => $value) {
                    if (false === \is_numeric($key)) {
                        if (true === \in_array($key, static::$keys, true)) {
                            $returns[$key] = \rawurldecode($value);
                        } else {
                            $returns['properties'][$key] = \rawurldecode($value);
                        }
                    }
                }

                return $returns;
            }
        }

        return [];
    }
}
