<?php declare(strict_types=1);

namespace Limepie;

/**
 * 복호화 가능한 문자열로 암호화.
 *
 * @category      system
 *
 * @param mixed $key
 */
// function encrypt($key)
// {
//     return Encrypt::pack($key);
// }
// function decrypt($key)
// {
//     return Encrypt::unpack($key);
// }

class Crypt
{
    protected static $_key = 'kkll';

    protected static $_cipher = 'aes-256-cfb';

    protected static $_hashAlgo = 'sha256';

    protected static $availableCiphers = [];

    public static function setAlgo($algo)
    {
        static::$_hashAlgo = $algo;
    }

    public static function getCipher()
    {
        return static::$_cipher;
    }

    public static function getKey()
    {
        return static::$_key;
    }

    public static function getAlgo()
    {
        return static::$_hashAlgo;
    }

    public static function getAvailableHashAlgos()
    {
        return \hash_algos();
    }

    public static function pack2($plaintext, $key = null)
    {
        if (false === \function_exists('openssl_cipher_iv_length')) {
            throw new Exception('openssl extension is required');
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

        if (!\in_array($cipher, static::getAvailableCiphers(), true)) {
            throw new Exception('Cipher algorithm is unknown');
        }
        $hashAlgo = static::getAlgo();

        if (!\in_array($hashAlgo, static::getAvailableHashAlgos(), true)) {
            throw new Exception('Hash algorithm is unknown');
        }
        $key        = \hash($hashAlgo, $encryptKey, true);
        $iv         = \openssl_random_pseudo_bytes(16);
        $ciphertext = \openssl_encrypt($plaintext, $cipher, $key, \OPENSSL_RAW_DATA, $iv);
        $hash       = \hash_hmac($hashAlgo, $ciphertext, $key, true);

        return $iv . $hash . $ciphertext;
    }

    public static function unpack2($ivHashCiphertext, $key = null)
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

        if (!\in_array($cipher, static::getAvailableCiphers(), true)) {
            throw new Exception('Cipher algorithm is unknown');
        }
        $hashAlgo = static::getAlgo();

        if (!\in_array($hashAlgo, static::getAvailableHashAlgos(), true)) {
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
}

class Encrypt extends Crypt
{
    public static $secureKey = '0123456789';

    public static function dec1($msg, $key = '')
    {
        $string = '';

        while ($msg) {
            $secureKey = \pack('H*', \md5($key . self::$secureKey));
            $dec_limit = \strlen(\substr($msg, 0, 16));
            $buffer    = self::_bytexor(\substr($msg, 0, 16), $secureKey, $dec_limit);
            $string .= $buffer;
            $msg = \substr($msg, 16);
        }

        return $string;
    }

    public static function enc2($msg, $key = '')
    {
        $msg_len = \strlen($msg);
        $string  = '';

        while ($msg) {
            $secureKey = \pack('H*', \md5($key . self::$secureKey));
            $buffer    = \substr($msg, 0, 16);
            $enc_limit = \strlen($buffer);
            $string .= self::_bytexor($buffer, $secureKey, $enc_limit);
            $msg = \substr($msg, 16);
        }

        return $string;
    }

    public static function pack($str, $key = '')
    {
        return \rawurlencode(self::pack2(\json_encode($str), $key));
    }

    public static function unpack($str, $key = '')
    {
        return \json_decode(self::unpack2(\rawurldecode($str), $key), true);
    }

    private static function _bytexor($a, $b, $ilimit)
    {
        $c = '';

        for ($i = 0; $i < $ilimit; ++$i) {
            $c .= $a[$i] ^ $b[$i];
        }

        return $c;
    }
}

// https://stackoverflow.com/questions/9262109/simplest-two-way-encryption-using-php
class UnsafeCrypto
{
    public const METHOD = 'aes-256-ctr';

    /**
     * Encrypts (but does not authenticate) a message.
     *
     * @param string $message - plaintext message
     * @param string $key     - encryption key (raw binary expected)
     * @param bool   $encode  - set to TRUE to return a base64-encoded
     *
     * @return string (raw binary)
     */
    public static function encrypt($message, $key, $encode = false)
    {
        $nonceSize = \openssl_cipher_iv_length(self::METHOD);
        $nonce     = \openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = \openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            \OPENSSL_RAW_DATA,
            $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        if ($encode) {
            return \base64_encode($nonce . $ciphertext);
        }

        return $nonce . $ciphertext;
    }

    /**
     * Decrypts (but does not verify) a message.
     *
     * @param string $message - ciphertext message
     * @param string $key     - encryption key (raw binary expected)
     * @param bool   $encoded - are we expecting an encoded string?
     *
     * @return string
     */
    public static function decrypt($message, $key, $encoded = false)
    {
        if ($encoded) {
            $message = \base64_decode($message, true);

            if (false === $message) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize  = \openssl_cipher_iv_length(self::METHOD);
        $nonce      = \mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = \mb_substr($message, $nonceSize, null, '8bit');

        return \openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            \OPENSSL_RAW_DATA,
            $nonce
        );
    }
}
class SaferCrypto extends UnsafeCrypto
{
    public const HASH_ALGO = 'sha256';

    /**
     * Encrypts then MACs a message.
     *
     * @param string $message - plaintext message
     * @param string $key     - encryption key (raw binary expected)
     * @param bool   $encode  - set to TRUE to return a base64-encoded string
     *
     * @return string (raw binary)
     */
    public static function encrypt($message, $key, $encode = false)
    {
        [$encKey, $authKey] = self::splitKeys($key);

        // Pass to UnsafeCrypto::encrypt
        $ciphertext = parent::encrypt($message, $encKey);

        // Calculate a MAC of the IV and ciphertext
        $mac = \hash_hmac(self::HASH_ALGO, $ciphertext, $authKey, true);

        if ($encode) {
            return \base64_encode($mac . $ciphertext);
        }
        // Prepend MAC to the ciphertext and return to caller
        return $mac . $ciphertext;
    }

    /**
     * Decrypts a message (after verifying integrity).
     *
     * @param string $message - ciphertext message
     * @param string $key     - encryption key (raw binary expected)
     * @param bool   $encoded - are we expecting an encoded string?
     *
     * @return string (raw binary)
     */
    public static function decrypt($message, $key, $encoded = false)
    {
        [$encKey, $authKey] = self::splitKeys($key);

        if ($encoded) {
            $message = \base64_decode($message, true);

            if (false === $message) {
                throw new Exception('Encryption failure');
            }
        }

        // Hash Size -- in case HASH_ALGO is changed
        $hs  = \mb_strlen(\hash(self::HASH_ALGO, '', true), '8bit');
        $mac = \mb_substr($message, 0, $hs, '8bit');

        $ciphertext = \mb_substr($message, $hs, null, '8bit');

        $calculated = \hash_hmac(
            self::HASH_ALGO,
            $ciphertext,
            $authKey,
            true
        );

        if (!self::hashEquals($mac, $calculated)) {
            throw new Exception('Encryption failure');
        }

        // Pass to UnsafeCrypto::decrypt
        return parent::decrypt($ciphertext, $encKey);
    }

    /**
     * Splits a key into two separate keys; one for encryption
     * and the other for authenticaiton.
     *
     * @param string $masterKey (raw binary)
     *
     * @return array (two raw binary strings)
     */
    protected static function splitKeys($masterKey)
    {
        // You really want to implement HKDF here instead!
        return [
            \hash_hmac(self::HASH_ALGO, 'ENCRYPTION', $masterKey, true),
            \hash_hmac(self::HASH_ALGO, 'AUTHENTICATION', $masterKey, true),
        ];
    }

    /**
     * Compare two strings without leaking timing information.
     *
     * @param string $a
     * @param string $b
     *
     * @ref https://paragonie.com/b/WS1DLx6BnpsdaVQW
     *
     * @return bool
     */
    protected static function hashEquals($a, $b)
    {
        if (\function_exists('hash_equals')) {
            return \hash_equals($a, $b);
        }
        $nonce = \openssl_random_pseudo_bytes(32);

        return \hash_hmac(self::HASH_ALGO, $a, $nonce) === \hash_hmac(self::HASH_ALGO, $b, $nonce);
    }
}
