<?php declare(strict_types=1);

namespace Limepie\Http;

class Response extends \Limepie\Exception
{
    public $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
