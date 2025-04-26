<?php declare(strict_types=1);

namespace Limepie\Http;

use Limepie\Exception;

class Response extends Exception
{
    public $response;

    public function __construct($response)
    {
        $this->response = $response;

        if (!\headers_sent()) {
            if (!isset($_SESSION)) {
                \session_start();
            }
            $_SESSION['pending_request'] = [
                'url'       => $_SERVER['REQUEST_URI'],
                'method'    => $_SERVER['REQUEST_METHOD'],
                'post_data' => $_POST,
                'headers'   => \Limepie\getHttpHeader(),
            ];
        }
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function __debugInfo()
    {
        $data = (array) $this;
        unset($data['response']);

        return $data;
    }
}
