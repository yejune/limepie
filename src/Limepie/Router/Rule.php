<?php declare(strict_types=1);

namespace Limepie\Router;

class Rule
{
    public static $keys = [
        'module',
        'namespace',
        'controller',
        'action',
        'path',
        'next',
    ];

    public static $allowMethods = [
        'post',
        'get',
        'delete',
        'put',
    ];

    public static function getMatched(string $pattern, string $subject, array $default) : array
    {
        $matches = [];

        if (1 === \preg_match($pattern, $subject, $matches)) {
            $allowMethods = [];

            if (true === isset($default['methods'])) {
                if (true === \is_array($default['methods'])) {
                    foreach ($default['methods'] as $method) {
                        if ('all' == $method) {
                            $allowMethods = static::$allowMethods;

                            break;
                        }
                        $allowMethods[] = $default;
                    }
                }
            } elseif (true === isset($default['method'])) {
                if ('all' == $default['method']) {
                    $allowMethods = static::$allowMethods;
                } else {
                    $allowMethods[] = $default['method'];
                }
            } else {
                $allowMethods = static::$allowMethods;
            }

            if (true === \in_array(\strtolower($_SERVER['REQUEST_METHOD'] ?? 'get'), $allowMethods, true) && $matches) {
                $returns               = [];
                $returns['properties'] = [];

                foreach ($default as $key => $value) {
                    if (false === \is_numeric($key)) {
                        if (true === \in_array($key, static::$keys, true)) {
                            $returns[$key] = $value;
                        } else {
                            if ('properties' === $key) {
                                $returns['properties'] = \Limepie\array_merge_deep($returns['properties'], $value);
                            } else {
                                $returns['properties'][$key] = $value;
                            }
                        }
                    }
                }

                foreach ($matches as $key => $value) {
                    if (false === \is_numeric($key)) {
                        if (true === \in_array($key, static::$keys, true)) {
                            $returns[$key] = $value;
                        } else {
                            $returns['properties'][$key] = $value;
                        }
                    }
                }

                return $returns;
            }
        }

        return [];
    }
}
