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
        'prev',
        'method',
    ];

    public static $allowMethods = [
        'post',
        'get',
        'delete',
        'put',
        'patch',
    ];

    public static function getMatched(string $pattern, string $subject, array $default) : array
    {
        $matches = [];

        if (false !== \strpos($pattern, '{')) {
            $pattern = \preg_replace('#\{([^\}0-9]+)\}#', '(?P<$1>[0-9a-zA-Z_\-\.]+)', $pattern);
        }

        if (1 === \preg_match($pattern, $subject, $matches)) {
            $allowMethods = [];

            if (true === isset($default['methods'])) {
                if (true === \is_array($default['methods'])) {
                    if (true === \in_array('all', $default['methods'], true)) {
                        $allowMethods = static::$allowMethods;
                    } else {
                        foreach ($default['methods'] as $method) {
                            $index = \array_search($method, $allowMethods, true);

                            if (false !== $index && true === isset($allowMethods[$index])) {
                                $allowMethods[$index] = $method;
                            } else {
                                $allowMethods[] = $method;
                            }
                        }
                    }
                }
            } elseif (true === isset($default['method'])) {
                if (true === \is_array($default['method'])) {
                    throw new \Limepie\Exception('Change property name method to methods');
                }

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
