<?php declare(strict_types=1);

namespace Limepie;

class Cookie
{
    public static $domain = '';

    public static $keyStores = [];

    //private static $cookie = [];

    public function __construct($option = [])
    {
        if ($option['domain']) {
            self::$domain = $option['domain'];
        }
    }

    public static function setDomain($domainName)
    {
        self::$domain = $domainName;
    }

    public static function getDomain()
    {
        return self::$domain;
    }

    public static function setKeyStore($key, $value)
    {
        self::$keyStores[$key] = $value;
    }

    public static function getKeyStore($key)
    {
        return self::$keyStores[$key] ?? null;
    }

    public static function set($key, $value, $expires = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true, $samesite = '')
    {
        $_value = Encrypt::pack($value);

        if (self::setRaw($key, $_value, $expires, $path, $domain, $secure, $httpOnly, $samesite)) {
            return $_value;
        }

        return false;
    }

    public static function get($key, $check = false)
    {
        return self::isCookie($key) ? Encrypt::unpack(self::getRaw($key)) : null;
    }

    public static function setRaw($key, $value, $expires = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true, $samesite = '')
    {
        if (true === \is_array($expires)) {
            foreach (['expires', 'path', 'domain', 'secure', 'httponly', 'samesite'] as $cookieKey) {
                if (true === isset($expires[$cookieKey])) {
                    ${$cookieKey} = $expires[$cookieKey];
                }
            }

            if (false === isset($expires['expires'])) {
                $expires = 0;
            }
        }

        $key    = self::sethost($key);
        $domain = true === (null === $domain) ? self::$domain : $domain;

        if (4096 < \strlen($value)) {
            die('4 KB per cookie maximum');
        }

        if (0 < $expires && \time() >= $expires) {
            $expires = \time() + $expires;
        }

        $_COOKIE[$key] = $value;

        $options = [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => $samesite,
        ];

        return \setcookie($key, $value, $options);
        //return \setcookie($key, $value, $expires, $path, $domain, $secure, $httpOnly);
    }

    public static function isCookie($key)
    {
        return true === isset($_COOKIE[$key]);
    }

    public static function getRaw($key)
    {
        $key = self::sethost($key);

        return $_COOKIE[$key] ?? false;
    }

    public static function remove($key, $value = '', $expires = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true, $samesite = '')
    {
        if (true === \is_array($expires)) {
            foreach (['expires', 'path', 'domain', 'secure', 'httponly', 'samesite'] as $cookieKey) {
                if (true === isset($expires[$cookieKey])) {
                    ${$cookieKey} = $expires[$cookieKey];
                }
            }

            if (false === isset($expires['expires'])) {
                $expires = 0;
            }
        } else {
            $expires = 0;
        }
        $key    = self::sethost($key);
        $domain = true === (null === $domain) ? self::$domain : $domain;

        if (true === isset($_COOKIE[$key])) {
            unset($_COOKIE[$key], $_REQUEST[$key]);
            $_COOKIE[$key] = $_REQUEST[$key] = null;

            $options = [
                'expires'  => $expires,
                'path'     => $path,
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httpOnly,
                'samesite' => $samesite,
            ];

            return \setcookie($key, '', $options);
            //return \setcookie($key, '', \time() - 3600, $path, $domain, $secure, $httpOnly);
        }

        return false;
    }

    private static function sethost($key)
    {
        return $key;
    }
}

function store($key, $value = null)
{
    if ($value) {
        return Cookie::set($key, $value);
    }

    return Cookie::get($key);
}
