<?php declare(strict_types=1);

namespace Limepie;

class Response
{
    public $statusCode = 200;

    public $statusCodes = [
        // INFORMATIONAL CODES
        100 => 'Continue',                        // RFC 7231, 6.2.1
        101 => 'Switching Protocols',             // RFC 7231, 6.2.2
        102 => 'Processing',                      // RFC 2518, 10.1
        103 => 'Early Hints',
        // SUCCESS CODES
        200 => 'OK',                              // RFC 7231, 6.3.1
        201 => 'Created',                         // RFC 7231, 6.3.2
        202 => 'Accepted',                        // RFC 7231, 6.3.3
        203 => 'Non-Authoritative Information',   // RFC 7231, 6.3.4
        204 => 'No Content',                      // RFC 7231, 6.3.5
        205 => 'Reset Content',                   // RFC 7231, 6.3.6
        206 => 'Partial Content',                 // RFC 7233, 4.1
        207 => 'Multi-status',                    // RFC 4918, 11.1
        208 => 'Already Reported',                // RFC 5842, 7.1
        226 => 'IM Used',                         // RFC 3229, 10.4.1
        // REDIRECTION CODES
        300 => 'Multiple Choices',                // RFC 7231, 6.4.1
        301 => 'Moved Permanently',               // RFC 7231, 6.4.2
        302 => 'Found',                           // RFC 7231, 6.4.3
        303 => 'See Other',                       // RFC 7231, 6.4.4
        304 => 'Not Modified',                    // RFC 7232, 4.1
        305 => 'Use Proxy',                       // RFC 7231, 6.4.5
        306 => 'Switch Proxy',                    // RFC 7231, 6.4.6 (Deprecated)
        307 => 'Temporary Redirect',              // RFC 7231, 6.4.7
        308 => 'Permanent Redirect',              // RFC 7538, 3
        // CLIENT ERROR
        400 => 'Bad Request',                     // RFC 7231, 6.5.1
        401 => 'Unauthorized',                    // RFC 7235, 3.1
        402 => 'Payment Required',                // RFC 7231, 6.5.2
        403 => 'Forbidden',                       // RFC 7231, 6.5.3
        404 => 'Not Found',                       // RFC 7231, 6.5.4
        405 => 'Method Not Allowed',              // RFC 7231, 6.5.5
        406 => 'Not Acceptable',                  // RFC 7231, 6.5.6
        407 => 'Proxy Authentication Required',   // RFC 7235, 3.2
        408 => 'Request Time-out',                // RFC 7231, 6.5.7
        409 => 'Conflict',                        // RFC 7231, 6.5.8
        410 => 'Gone',                            // RFC 7231, 6.5.9
        411 => 'Length Required',                 // RFC 7231, 6.5.10
        412 => 'Precondition Failed',             // RFC 7232, 4.2
        413 => 'Request Entity Too Large',        // RFC 7231, 6.5.11
        414 => 'Request-URI Too Large',           // RFC 7231, 6.5.12
        415 => 'Unsupported Media Type',          // RFC 7231, 6.5.13
        416 => 'Requested range not satisfiable', // RFC 7233, 4.4
        417 => 'Expectation Failed',              // RFC 7231, 6.5.14
        418 => "I'm a teapot",                    // RFC 7168, 2.3.3
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',            // RFC 4918, 11.2
        423 => 'Locked',                          // RFC 4918, 11.3
        424 => 'Failed Dependency',               // RFC 4918, 11.4
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',                // RFC 7231, 6.5.15
        428 => 'Precondition Required',           // RFC 6585, 3
        429 => 'Too Many Requests',               // RFC 6585, 4
        431 => 'Request Header Fields Too Large', // RFC 6585, 5
        451 => 'Unavailable For Legal Reasons',   // RFC 7725, 3
        499 => 'Client Closed Request',
        // SERVER ERROR
        500 => 'Internal Server Error',           // RFC 7231, 6.6.1
        501 => 'Not Implemented',                 // RFC 7231, 6.6.2
        502 => 'Bad Gateway',                     // RFC 7231, 6.6.3
        503 => 'Service Unavailable',             // RFC 7231, 6.6.4
        504 => 'Gateway Time-out',                // RFC 7231, 6.6.5
        505 => 'HTTP Version not supported',      // RFC 7231, 6.6.6
        506 => 'Variant Also Negotiates',         // RFC 2295, 8.1
        507 => 'Insufficient Storage',            // RFC 4918, 11.5
        508 => 'Loop Detected',                   // RFC 5842, 7.2
        510 => 'Not Extended',                    // RFC 2774, 7
        511 => 'Network Authentication Required',  // RFC 6585, 6
    ];

    public $errors = [];

    public $message;

    public $content = '';

    public $assign = [];

    public $define = [];

    public $payload = [];

    public $headers = [];

    public $redirect = false;

    final public function __construct()
    {
        if (true === \method_exists($this, '__init')) {
            \call_user_func([$this, '__init']);
        } elseif (true === \method_exists($this, '__init__')) {
            \call_user_func([$this, '__init__']);
        }
    }

    public function setJsonContent(array $array = [], $option = null)
    {
        $this->content = \json_encode($array, $option);

        return $this;
    }

    public function setXmlcontent($str) : self
    {
        $this->content = $str;

        return $this;
    }

    public function setRawContent($str) : self
    {
        $this->content = $str;

        return $this;
    }

    public function content($str) : self
    {
        $this->content = $str;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function send() : void
    {
        echo $this->content;
    }

    // alias dispatcher forward
    public function forward($rule) : ?self
    {
        if (true === Di::has('dispatcher')) {
            return Di::get('dispatcher')->forward($rule);
        }

        // ERRORCODE: 20008, service provider not found
        throw new Exception('"dispatcher" service provider not found', 20008);
    }

    public function redirect($location, $seconds = 0)
    {
        $this->content  = '<meta http-equiv="refresh" content="' . $seconds . '; url=' . $location . '" />';
        $this->redirect = true;

        return $this;
    }

    public function getStatusMessage(int $code = 0)
    {
        if ($code) {
            return $this->statusCodes[$code] ?? '';
        }

        return $this->statusCodes[$this->statusCode] ?? '';
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    // public function xsetStatusCode(int $code, ?string $message = null) : self
    // {
    //     $this->statusCode = $code;

    //     // if an empty message is given we try and grab the default for this
    //     // status code. If a default doesn't exist, stop here.
    //     if (null === $message) {
    //         // See: http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml

    //         if (false === isset($this->statusCodes[$code])) {
    //             throw new Exception('Non-standard statuscode given without a message');
    //         }

    //         $message = $this->statusCodes[$code];
    //     }

    //     $this->setHeader('HTTP/1.1 ' . $code . ' ' . $message);

    //     // We also define a 'Status' header with the HTTP status
    //     $this->setHeader('Status', $code . ' ' . $message);

    //     return $this;
    // }

    // public function xsetHeaders(array $headers = []) : self
    // {
    //     foreach ($headers as $header => $value) {
    //         if (false === $this->setHeader($header, $value)) {
    //             return false;
    //         }
    //     }

    //     return $this;
    // }

    public function xsetHeader($header, $value = null) : self
    {
        if (false === \headers_sent()) {
            if (null === $value) {
                if (\strpos($header, ':') || 'HTTP/' === \substr($header, 0, 5)) {
                    \header($header, true);
                } else {
                    \header($header . ': ', true);
                }
            } else {
                \header(\ucfirst($header) . ': ' . $value, true);
            }
        }

        return $this;
    }

    /**
     * Sets the response content-type mime, optionally the charset.
     *
     *<code>
     * $response->setContentType("application/pdf");
     * $response->setContentType("text/plain", "UTF-8");
     *</code>
     *
     * @param mixed $assign
     */
    // public function xsetContentType(string $contentType, $charset = null) : self
    // {
    //     if (null === $charset) {
    //         $this->setHeader('Content-Type', $contentType);
    //     } else {
    //         $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
    //     }

    //     return $this;
    // }

    public function assign($assign)
    {
        $this->assign = $assign;

        return $this;
    }

    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function headers($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function header($headerName, $headerValue)
    {
        $this->headers[$headerName] = $headerValue;

        return $this;
    }

    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    public function define($define)
    {
        $this->define = $define;

        return $this;
    }
}
