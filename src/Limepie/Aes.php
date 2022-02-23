<?php declare(strict_types=1);

namespace Limepie;

class Aes
{
    public static $salt = 'kkll';

    public static $cipher = 'aes-256-cfb';

    public static $hashAlgo = 'sha256';

    public static $availableCiphers = [];

    public static function setAlgo($algo)
    {
        static::$hashAlgo = $algo;
    }

    public static function getCipher()
    {
        return static::$cipher;
    }

    public static function getKey()
    {
        return static::$salt;
    }

    public static function getAlgo()
    {
        return static::$hashAlgo;
    }

    public static function getAvailableHashAlgos()
    {
        return \hash_algos();
    }

    public static function encrypt($plaintext, $key = null)
    {
        if (false === \function_exists('openssl_cipher_iv_length')) {
            throw new Exception('openssl extension is required1');
        }

        if (!$key) {
            $encryptKey = static::getKey();
        } else {
            $encryptKey = $key;
        }

        if (!$encryptKey) {
            throw new Exception('Encryption key cannot be empty');
        }
        $cipher = static::getCipher();

        if (false === \in_array($cipher, static::getAvailableCiphers(), true)) {
            throw new Exception('Cipher algorithm is unknown');
        }
        $hashAlgo = static::getAlgo();

        if (false === \in_array($hashAlgo, static::getAvailableHashAlgos(), true)) {
            throw new Exception('Hash algorithm is unknown');
        }
        $key        = \hash($hashAlgo, $encryptKey, true);
        $iv         = \openssl_random_pseudo_bytes(16);
        $ciphertext = \openssl_encrypt($plaintext, $cipher, $key, \OPENSSL_RAW_DATA, $iv);
        $hash       = \hash_hmac($hashAlgo, $ciphertext, $key, true);

        return $iv . $hash . $ciphertext;
    }

    public static function decrypt($ivHashCiphertext, $key = null)
    {
        if (false === \function_exists('openssl_cipher_iv_length')) {
            throw new Exception('openssl extension is required');
        }

        if (!$key) {
            $decryptKey = static::getKey();
        } else {
            $decryptKey = $key;
        }

        if (!$decryptKey) {
            throw new Exception('Decryption key cannot be empty');
        }
        $cipher = static::getCipher();

        if (false === \in_array($cipher, static::getAvailableCiphers(), true)) {
            throw new Exception('Cipher algorithm is unknown');
        }
        $hashAlgo = static::getAlgo();

        if (false === \in_array($hashAlgo, static::getAvailableHashAlgos(), true)) {
            throw new Exception('Hash algorithm is unknown');
        }
        $key        = \hash($hashAlgo, $decryptKey, true);
        $iv         = \substr($ivHashCiphertext, 0, 16);
        $hash       = \substr($ivHashCiphertext, 16, 32);
        $ciphertext = \substr($ivHashCiphertext, 48);

        if (\hash_hmac($hashAlgo, $ciphertext, $key, true) !== $hash) {
            return null;
        }

        return \openssl_decrypt($ciphertext, $cipher, $key, \OPENSSL_RAW_DATA, $iv);
    }

    public static function getAvailableCiphers()
    {
        if (!static::$availableCiphers) {
            static::initializeAvailableCiphers();
        }

        return static::$availableCiphers;
    }

    protected static function initializeAvailableCiphers()
    {
        if (!\function_exists('openssl_get_cipher_methods')) {
            throw new Exception('openssl extension is required');
        }

        static::$availableCiphers = \openssl_get_cipher_methods(true);
    }

    public static function pack($message)
    {
        return \gzcompress(
            \Limepie\Aes::encrypt(
                \serialize($message)
            ),
            9
        );
    }

    public static function unpack($message)
    {
        return \unserialize(
            \Limepie\Aes::decrypt(
                \gzuncompress($message)
            )
        );
    }
}
