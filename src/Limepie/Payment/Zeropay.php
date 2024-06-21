<?php declare(strict_types=1);

namespace Limepie\Payment;

class Zeropay
{
    public $mid;

    public $key;

    public $access_token;

    public $cacel_url = 'https://zpg.zeropaypoint.or.kr/api_v1_payment_partial_cancel.jct';

    public function __construct($mid, $key, $access_token)
    {
        $this->mid          = $mid;
        $this->key          = $key;
        $this->access_token = $access_token;
    }

    public function partialCancel($data)
    {
        $date     = \date('YmdHis');
        $jsonBody = \json_encode($data);

        $body = [
            'MID'      => $this->mid,
            'RQ_DTIME' => $date,
            'TNO'      => $date,
            'EV'       => \Limepie\Payment\Zeropay::EncryptAesToHexa($jsonBody, $this->key),
            'VV'       => \Limepie\Payment\Zeropay::getHmacSha256($jsonBody, $this->key),
        ];

        $result     = \Limepie\Payment\Zeropay::post($this->cacel_url, $body, $this->access_token);
        $resultJson = \json_decode($result, true);

        return \json_decode(\Limepie\Payment\Zeropay::DecryptAesFromHexa($resultJson['EV'], $this->key), true);
    }

    public static function EncryptAesToHexa($input, $key)
    {
        if (null == $input || null == $key) {
            return null;
        }
        $ivBytes = \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0);
        $byteKey = \hex2bin($key);
        $cipher  = \openssl_encrypt($input, 'aes-128-cbc', $byteKey, 1, $ivBytes);

        return \bin2hex($cipher);
    }

    public static function DecryptAesFromHexa($input, $key)
    {
        if (null == $input || null == $key) {
            return null;
        }
        $ivBytes
                 = \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0) . \chr(0);
        $byteKey = \hex2bin($key);

        return \openssl_decrypt(\hex2bin($input), 'aes-128-cbc', $byteKey, 1, $ivBytes);
    }

    public static function getHmacSha256($input, $key)
    {
        if (null == $input || null == $key) {
            return null;
        }
        $byteKey = \hex2bin($key);
        $cipher  = \hash_hmac('sha256', $input, $byteKey, true);

        return \bin2hex($cipher);
    }

    public static function VerifyMac($skey, $data, $hmac)
    {
        $decrytedData = static::DecryptAesFromHexa($data, $skey);
        $checkHmac    = static::getHmacSha256($decrytedData, $skey);

        if ($hmac == $checkHmac) {
            return true;
        }

        return false;
    }

    public static function post($url, array $fields = [], $access_token)
    {
        $body = \json_encode($fields);
        $ch   = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_HEADER, true);
        // \curl_setopt($ch, CURLOPT_HTTPHEADER, []);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json; charset=UTF-8',
            'Authorization: OnlineAK ' . $access_token,
        ]);
        \curl_setopt($ch, CURLOPT_VERBOSE, true);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = \curl_exec($ch);

        $info  = \curl_getinfo($ch);
        $start = $info['header_size'];
        $body  = \substr($result, $start, \strlen($result) - $start);

        \curl_close($ch);

        return $body;
    }
}
