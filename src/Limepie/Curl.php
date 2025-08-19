<?php declare(strict_types=1);

namespace Limepie;

class Curl
{
    public $referer = '';

    public $userAgent;

    public $cookie = '';

    public $url = '';

    public $timeout;

    public $connectTimeout;

    public $browser = [
        'chrome' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.108 Safari/537.36',
    ];

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    public function setUserAgent($userAgent)
    {
        if (true === isset($this->browser[$userAgent])) {
            $userAgent = $this->browser[$userAgent];
        }
        $this->userAgent = $userAgent;
    }

    public function getContent() : Curl\Content
    {
        $url = $this->url;

        $curl = \curl_init();
        \curl_setopt($curl, \CURLOPT_URL, $url);
        \curl_setopt($curl, \CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($curl, \CURLOPT_HEADER, true);
        //\curl_setopt($curl, \CURLOPT_FOLLOWLOCATION, true);

        if ($this->referer) {
            \curl_setopt($curl, \CURLOPT_REFERER, $this->referer);
        }

        if ($this->timeout) {
            \curl_setopt($curl, \CURLOPT_TIMEOUT, $this->timeout);
        }

        if ($this->connectTimeout) {
            \curl_setopt($curl, \CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        if ($this->userAgent) {
            \curl_setopt($curl, \CURLOPT_USERAGENT, $this->userAgent);
        }

        if ($this->cookie) {
            \curl_setopt($curl, \CURLOPT_COOKIE, $this->cookie);
        }

        \curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);

        $response = \curl_exec($curl);

        // Check HTTP status code
        if (false === \curl_errno($curl)) {
            switch ($httpStatusCode = \curl_getinfo($curl, \CURLINFO_HTTP_CODE)) {
                case 200: // OK
                    break;
                default:
                    throw new \Exception('Unexpected HTTP code: ' . $httpStatusCode);
            }
        }

        if($response) {
            $content = new \Limepie\Curl\Content();
            $content->setResponse($response);
            $content->setInfo(\curl_getinfo($curl));

            $headerSize = curl_getinfo($curl, \CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $content->setHeader($header);
            $content->setBody($body);
        } else {
            return null;
        }

        \curl_close($curl);

        return $content;
    }
}
