<?php declare(strict_types=1);

namespace Limepie\Curl;

class Content
{
    public $response;

    public $headers;

    public $info = [];

    public $body;

    public function __construct()
    {
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setInfo($info)
    {
        $this->info = $info;
    }

    public function setHeader($headerContent)
    {
        $headers     = [];
        $arrRequests = \explode("\r\n\r\n", $headerContent);

        for ($index = 0; \count($arrRequests) - 1 > $index; $index++) {
            foreach (\explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if (0 === $i) {
                    $headers[$index]['Status Code'] = $line;
                } else {
                    [$key, $value]         = \explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $this->headers = $headers;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getJsonBody()
    {
        return \json_decode($this->body, true);
    }
}
