<?php

declare(strict_types=1);

namespace Limepie;

class Request
{
    public $rawBody;

    public $bodies = [];

    public $domain = '';

    public $subDomain = '';

    public $baseDomain = '';

    public $url;

    public $scheme;

    public $host;

    public $path;

    public $endpoint = '';

    public $targetEndpoint = '';

    public $requestId;

    public $httpMethodParameterOverride = false;

    public $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    public $language = 'ko';

    public $locale = 'ko_KR';

    public $query;

    public $pathStore = [];

    public $locales = [
        'ko' => 'ko_KR',
        'en' => 'en_US',
        'zh' => 'zh_CN',
        'ja' => 'ja_JP',
    ];

    final public function __construct()
    {
        $this->rawBody = \file_get_contents('php://input');
        $this->bodies  = $this->getFormData();

        $this->language = $this->getBestLanguage();

        if (true === isset($this->locales[$this->language])) {
            $this->locale = $this->locales[$this->language];
        }

        $this->getHost();

        [$this->subDomain , $this->baseDomain] = \explode('.', $this->host, 2);
    }

    public function addPath($name, Request\Path $path)
    {
        $this->pathStore[$name] = $path;
    }

    public function __call(string $name, array $arguments = [])
    {
        if (0 === \strpos($name, 'getPathBy')) {
            return $this->buildGet(\Limepie\decamelize(\substr($name, 9)), $arguments);
        }

        throw (new Exception('"' . $name . '" method not found', 404))
            ->setDebugMessage('error', __FILE__, __LINE__)
            ->setDebugMessage('유효하지 않은 요청입니다. 잠시후 다시 시도하세요.')
        ;
    }

    public function buildGet($name, $arguments)
    {
        if (false === isset($this->pathStore[$name])) {
            throw new Exception('"' . $name . '" path not found');
        }

        if ($arguments) {
            return $this->pathStore[$name]->slice(...$arguments);
        }

        return $this->pathStore[$name];
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setEndpoint($path)
    {
        $this->endpoint = \rtrim((string) $path);

        return $this;
    }

    // public function getPath()
    // {
    //     return \rtrim($this->path, '/');
    // }

    public function setTargetEndpoint($path)
    {
        $this->targetEndpoint = \rtrim((string) $path, '/');

        return $this;
    }

    public function getTargetEndpoint()
    {
        return $this->targetEndpoint;
    }

    public function setLanguage($language)
    {
        return $this->setLocale($language);
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLocale($language, $default = 'ko')
    {
        $this->language = \explode('_', $language)[0];

        if (true === isset($this->locales[$this->language])) {
            $this->locale = $this->locales[$this->language];
        } else {
            if (true === isset($this->locales[$default])) {
                $this->language = $default;
                $this->locale   = $this->locales[$default];
            }
        }
    }

    public function bindTextDomain($domain, $path)
    {
        $charset = 'UTF-8';

        \setlocale(\LC_MESSAGES, $this->locale . '.' . $charset);
        \bindtextdomain($domain, $path);
        \bind_textdomain_codeset($domain, $charset);
        \textdomain($domain);

        // pr($domain, $path, $domain, "{$locale}.{$charset}");
        return $this->locale . '.' . $charset;
    }

    /**
     * Gets HTTP schema (http/https).
     */
    public function getScheme() : string
    {
        if (true === isset($_ENV['is_swoole']) && 1 === (int) $_ENV['is_swoole']) {
            $this->scheme = 'https';
        }

        if ($this->scheme) {
            return $this->scheme;
        }

        $https = $this->getServer('HTTP_X_FORWARDED_PROTO');

        if ($https) {
            $this->scheme = $https;
        } else {
            $https = $this->getServer('HTTPS');

            if ($https) {
                if ('off' === $https) {
                    $this->scheme = 'http';
                } else {
                    $this->scheme = 'https';
                }
            } else {
                $this->scheme = 'http';
            }
        }

        return (string) $this->scheme;
    }

    public function getHost() : string
    {
        if ($this->host) {
            return $this->host;
        }

        $this->host = $this->getServer('HTTP_HOST');

        if (!$this->host) {
            $this->host = $this->getServer('SERVER_NAME');

            if (!$this->host) {
                $this->host = $this->getServer('SERVER_ADDR');
            }
        }

        return $this->host;
    }

    public function getRequestUri()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function getDocumentUri()
    {
        return $_SERVER['DOCUMENT_URI'];
    }

    public function getQueryString($append = '')
    {
        if ($_SERVER['QUERY_STRING'] ?? false) {
            return $append . \ltrim($_SERVER['QUERY_STRING'], $append);
        }

        return '';
    }

    public function getFixPath($step = null, $length = null)
    {
        return \str_replace(
            $this->getTargetEndpoint(),
            $this->getEndpoint(),
            $this->getPathByModule($step, $length)
        );
    }

    public function getUrl()
    {
        $url = '';
        $url .= $this->getSchemeHost();
        $url .= $this->getRequestUri();

        return $url;
    }

    public function getSelf()
    {
        $url = '';
        $url .= $this->getUrl();

        return \strtok($url, '?');
    }

    public function getSchemeHost()
    {
        return $this->getScheme() . '://' . $this->getHost();
    }

    public function getBodies()
    {
        return $this->bodies;
    }

    // alias application getProperties
    public function getApplicationProperties()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getProperties();
        }

        // ERRORCODE: 20007, service provider not found
        throw new Exception('"application" service provider not found', 20007);
    }

    // alias application getStore
    public function getApplicationStore()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getStore();
        }

        // ERRORCODE: 20007, service provider not found
        throw new Exception('"application" service provider not found', 20007);
    }

    // alias application getNamespace
    public function getApplicationNamespaceName()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getNamespaceName();
        }

        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    // alias application getController
    public function getApplicationControllerName()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getControllerName();
        }

        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    // alias application getAction
    public function getApplicationActionName()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getActionName();
        }

        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20010);
    }

    // alias application getModulePath
    public function getApplicationPath()
    {
        if (true === Di::has('application')) {
            return Di::getApplication()->getPathByModule();
        }

        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    public function extractDomain($domain)
    {
        $matches = [];

        $parts = \explode('.', $domain, 2);

        return $parts[1];
    }

    public function getBaseDomain($host = null)
    {
        return $this->baseDomain;
    }

    public function setSubDomain($subDomain)
    {
        $this->subDomain = $subDomain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getDefaultHost()
    {
        return $this->getScheme() . '://www.' . $this->getBaseDomain();
    }

    public function getSubDomain($returnDefault = true)
    {
        if (\Limepie\is_cli()) {
            return 'cli';
        }

        return $this->subDomain;
    }

    public function getBestAccept() : string
    {
        return $this->getBestQuality($this->getAcceptableContent(), 'accept');
    }

    /**
     * Gets an array with mime/types and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT"].
     */
    public function getAcceptableContent() : array
    {
        return $this->getQualityHeader('HTTP_ACCEPT', 'accept');
    }

    /**
     * Gets content type which request has been made.
     */
    public function getContentType() : ?string
    {
        $contentType = $this->getServer('CONTENT_TYPE');

        if ($contentType) {
            return $contentType;
        }
        // @see https://bugs.php.net/bug.php?id=66606
        $httpContentType = $this->getServer('HTTP_CONTENT_TYPE');

        if ($httpContentType) {
            return $httpContentType;
        }

        return null;
    }

    /**
     * Gets HTTP raw request body.
     */
    public function getRawBody() : string
    {
        return $this->rawBody;
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com.
     */
    public function getHTTPReferer() : string
    {
        return $this->getServer('HTTP_REFERER');
    }

    /**
     * Gets decoded JSON HTTP raw request body
     * return <\stdClass> | array | bool.
     */
    public function getJsonRawBody(bool $associative = false)
    {
        $rawBody = $this->getRawBody();

        if ('string' !== \gettype($rawBody)) {
            return false;
        }

        return \json_decode($rawBody, $associative);
    }

    /**
     * Gets best language accepted by the browser/client from
     * _SERVER["HTTP_ACCEPT_LANGUAGE"].
     */
    public function getBestLanguage()
    {
        return $this->getBestQuality($this->getLanguages(), 'language');
    }

    /**
     * Gets languages array and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT_LANGUAGE"].
     */
    public function getLanguages() : array
    {
        return $this->getQualityHeader('HTTP_ACCEPT_LANGUAGE', 'language');
    }

    /**
     * Gets HTTP method which request has been made.
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if setHttpMethodParameterOverride(true) has been called.
     *
     * The method is always an uppercased string.
     */
    final public function getMethod() : string
    {
        $returnMethod  = '';
        $requestMethod = $this->getServer('REQUEST_METHOD');

        if ($requestMethod) {
            $returnMethod = \strtoupper($requestMethod);

            if ('POST' === $returnMethod) {
                $overridedMethod = $this->getHeader('X-HTTP-METHOD-OVERRIDE');

                if ($overridedMethod) {
                    $returnMethod = \strtoupper($overridedMethod);
                } elseif ($this->httpMethodParameterOverride) {
                    $returnMethod = \strtoupper($_REQUEST['_method']);
                }
            }
        }

        if (!$this->isValidHttpMethod($returnMethod)) {
            return 'GET';
        }

        return $returnMethod;
    }

    public function getHeader($key) : ?string
    {
        return $this->getServer($key);
    }

    /**
     * Gets HTTP user agent used to made the request.
     */
    public function getUserAgent() : string
    {
        return $this->getServer('HTTP_USER_AGENT');
    }

    /**
     * Gets information about the port on which the request is made.
     */
    public function getPort() : int
    {
        $host = $this->getServer('HTTP_HOST');

        if ($host) {
            if (false !== \strpos($host, ':')) {
                $pos = \strrpos($host, ':');

                if (false !== $pos) {
                    return (int) \substr($host, $pos + 1);
                }
            }

            return 'https' === $this->getScheme() ? 443 : 80;
        }

        return (int) $this->getServer('SERVER_PORT');
    }

    /**
     * Gets HTTP URI which request has been made.
     */
    final public function getURI() : string
    {
        return $this->getServer('REQUEST_URI');
    }

    public function getServer($key) : null|array|string
    {
        return $_SERVER[$key] ?? null;
    }

    // get $_REQUEST[$key]
    public function get($key)
    {
        return $_GET[$key] ?? null;
    }

    // get $_PUT[$key]
    public function getPut() {}

    // get $_GET[$key]
    public function getQuery($key)
    {
        return $_GET[$key] ?? null;
    }

    // get $_POST[$key]
    public function getPost($key)
    {
        return $_POST[$key] ?? null;
    }

    public function isCli()
    {
        return 'cli' === \php_sapi_name();
    }

    public function getRequestMethod()
    {
        return $this->getServer('REQUEST_METHOD');
    }

    public function getRequestId()
    {
        if (!$this->requestId) {
            if (true === isset($_SERVER['HTTP_REQUEST_ID'])) {
                $this->requestId = $_SERVER['HTTP_REQUEST_ID'];
            } else {
                $this->requestId = \Limepie\uniqid(32);
            }
        }

        return $this->requestId;
    }

    public function getFormData()
    {
        $rawBody = $this->getRawBody();

        $contentType = $this->getContentType();

        if ($contentType) {
            $contentType = \explode(';', $contentType)[0];
        }

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                $parserd = [];
                \parse_str($rawBody, $parserd);

                $this->bodies = $parserd;

                return $this->bodies;

                break;
            case 'multipart/form-data':
                if ('POST' === $_SERVER['REQUEST_METHOD']) {
                    if (
                        (empty($_POST) && empty($_FILES))
                        && 0 < $_SERVER['CONTENT_LENGTH']
                    ) {
                        // throw new Exception(\sprintf('The server was unable to handle that much POST data (%s bytes) due to its current configuration', $_SERVER['CONTENT_LENGTH']), 20012);
                    }
                    $this->bodies = $_POST;
                } elseif (
                    'DELETE' === $_SERVER['REQUEST_METHOD']
                    || 'PUT' === $_SERVER['REQUEST_METHOD']
                ) {
                    $this->bodies = \Limepie\parse_raw_http_request();
                }

                return $this->bodies;

                break;
            case 'application/xml':
                throw new Exception('xml content type not support', 415);

                break;

                break;
            case 'application/json':
            case 'text/javascript':
            default:
                $rawBody = \str_replace(' \\', '', $rawBody);
                $json    = \json_decode($rawBody, true);

                if (0 < \strlen($rawBody)) {
                    $type = \json_last_error();

                    if ($type) {
                        switch ($type) {
                            case \JSON_ERROR_DEPTH:
                                $message = 'Maximum stack depth exceeded';

                                break;
                            case \JSON_ERROR_CTRL_CHAR:
                                $message = 'Unexpected control character found';

                                break;
                            case \JSON_ERROR_SYNTAX:
                                $message = 'Syntax error, malformed JSON';

                                break;
                            case \JSON_ERROR_NONE:
                                $message = 'No errors';

                                break;
                            case \JSON_ERROR_UTF8:
                                $message = 'Malformed UTF-8 characters';

                                break;

                            default:
                                $message = 'Invalid JSON syntax';
                        }

                        throw new Exception($message, 20013);
                    }
                } else {
                    $json = [];
                }

                $this->bodies = $json;

                return $this->bodies;

                break;
        }
    }

    /**
     * Checks whether request has been made using ajax.
     */
    public function isAjax() : bool
    {
        return 'XMLHttpRequest' === $this->getServer('HTTP_X_REQUESTED_WITH');
    }

    /**
     * Checks whether HTTP method is CONNECT. if _SERVER["REQUEST_METHOD"]==="CONNECT".
     */
    public function isConnect() : bool
    {
        return 'CONNECT' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is DELETE. if _SERVER["REQUEST_METHOD"]==="DELETE".
     */
    public function isDelete() : bool
    {
        return 'DELETE' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is GET. if _SERVER["REQUEST_METHOD"]==="GET".
     */
    public function isGet() : bool
    {
        return 'GET' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is HEAD. if _SERVER["REQUEST_METHOD"]==="HEAD".
     */
    public function isHead() : bool
    {
        return 'HEAD' === $this->getMethod();
    }

    /**
     * Check if HTTP method match any of the passed methods
     * When strict is true it checks if validated methods are real HTTP methods.
     *
     * @param mixed $methods
     */
    public function isMethod($methods, bool $strict = false) : bool
    {
        $httpMethod = $this->getMethod();

        if ('string' === \gettype($methods)) {
            if ($strict && !$this->isValidHttpMethod($methods)) {
                throw new Exception('Invalid HTTP method: ' . $methods, 20013);
            }

            return $methods === $httpMethod;
        }

        if ('array' === \gettype($methods)) {
            foreach ($methods as $method) {
                if ($this->isMethod($method, $strict)) {
                    return true;
                }
            }

            return false;
        }

        if ($strict) {
            throw new Exception('Invalid HTTP method: non-string', 20014);
        }

        return false;
    }

    /**
     * Checks whether HTTP method is OPTIONS. if _SERVER["REQUEST_METHOD"]==="OPTIONS".
     */
    public function isOptions() : bool
    {
        return 'OPTIONS' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PATCH. if _SERVER["REQUEST_METHOD"]==="PATCH".
     */
    public function isPatch() : bool
    {
        return 'PATCH' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is POST. if _SERVER["REQUEST_METHOD"]==="POST".
     */
    public function isPost() : bool
    {
        return 'POST' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PUT. if _SERVER["REQUEST_METHOD"]==="PUT".
     */
    public function isPut() : bool
    {
        return 'PUT' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PURGE (Squid and Varnish support). if _SERVER["REQUEST_METHOD"]==="PURGE".
     */
    public function isPurge() : bool
    {
        return 'PURGE' === $this->getMethod();
    }

    /**
     * Checks whether request has been made using any secure layer.
     */
    public function isSecure() : bool
    {
        return 'https' === $this->getScheme();
    }

    // /**
    //  * Checks whether request has been made using SOAP.
    //  */
    // public function isSoap() : bool
    // {
    //     $tmp = $this->getServer('HTTP_SOAPACTION');

    //     if ($tmp) {
    //         return true;
    //     }
    //     $contentType = $this->getContentType();

    //     if (0 === \strlen($contentType)) {
    //         return false !== \strpos($contentType, 'application/soap+xml');
    //     }

    //     return false;
    // }

    /**
     * Checks whether HTTP method is TRACE. if _SERVER["REQUEST_METHOD"]==="TRACE".
     */
    public function isTrace() : bool
    {
        return 'TRACE' === $this->getMethod();
    }

    /**
     * Checks if a method is a valid HTTP method.
     */
    public function isValidHttpMethod(string $method) : bool
    {
        switch (\strtoupper($method)) {
            case 'GET':
            case 'POST':
            case 'PUT':
            case 'DELETE':
            case 'HEAD':
            case 'OPTIONS':
            case 'PATCH':
            case 'PURGE': // Squid and Varnish support
            case 'TRACE':
            case 'CONNECT':
                return true;
        }

        return false;
    }

    // multi file일때만 true
    public function isMultiFile($array, $isMulti = true)
    {
        if (
            true    === isset($array['name'])
            && true === isset($array['type'])
            && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            if (true === \is_array($array['name'])) {
                return true;
            }
        }

        return false;
    }

    // 파일인지, 멀티 체크안하고 파일이면 true
    public function isFile($array)
    {
        if (
            true    === isset($array['name'])
            && true === isset($array['type'])
            && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            return true;
        }

        return false;
    }

    public function normalizeFiles()
    {
        $out = [];

        foreach ($_FILES as $key => $file) {
            if (isset($file['name']) && \is_array($file['name'])) {
                $new = [];

                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
                    \array_walk_recursive($file[$k], function (&$data, $key, $k) {
                        $data = [$k => $data];
                    }, $k);
                    $new = \array_replace_recursive($new, $file[$k]);
                    \print_r($new);
                }
                $out[$key] = $new;
            } else {
                $out[$key] = $file;
            }
        }

        return $out;
    }

    public function getFixedFilesArray()
    {
        $walker = function ($arr, $fileInfokey, callable $walker) {
            $return = [];

            foreach ($arr as $k => $v) {
                if (true === \is_array($v)) {
                    $return[$k] = $walker($v, $fileInfokey, $walker);
                } else {
                    $return[$k][$fileInfokey] = $v;
                }
            }

            return $return;
        };

        $files = [];

        foreach ($_FILES as $name => $values) {
            // init for array_merge
            if (false === isset($files[$name])) {
                $files[$name] = [];
            }

            if (false === \is_array($values['error'])) {
                // normal syntax
                if (4 != $values['error']) {
                    $files[$name] = $values;
                }
            } else {
                foreach ($values as $fileInfoKey => $subArray) {
                    $files[$name] = \array_replace_recursive($files[$name], $walker($subArray, $fileInfoKey, $walker));
                }
            }
        }

        return $files;
    }

    public function getFileAll()
    {
        if (true === isset($_FILES) && $_FILES) {
            // return $this->normalizeFiles();

            $a = $this->getFixedFilesArray();

            return $a;
        }

        return [];
    }

    public function getDevice()
    {
        $device = '';

        if (true === isset($_SERVER['HTTP_USER_AGENT'])) {
            if (\stristr($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
                $device = 'ipad';
            } elseif (\stristr($_SERVER['HTTP_USER_AGENT'], 'iphone') || \strstr($_SERVER['HTTP_USER_AGENT'], 'iphone')) {
                $device = 'iphone';
            } elseif (\stristr($_SERVER['HTTP_USER_AGENT'], 'android')) {
                $device = 'android';
            } else {
                $device = '';
            }
        }

        return $device;
    }

    public function getAppScheme()
    {
        $appScheme = '';

        if (true === isset($_SERVER['HTTP_USER_AGENT'])) {
            if (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'instagram')) {
                $appScheme = 'instagram';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'kakaotalk')) {
                $appScheme = 'kakaotalk';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'samsungpay')) {
                $appScheme = 'samsungpay';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'naver')) {
                $appScheme = 'naversearchapp';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'daum')) {
                $appScheme = 'daumapps';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'fbav')) {
                $appScheme = 'fb';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'fban')) {
                $appScheme = 'fb';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'facebook')) {
                $appScheme = 'fb';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'googlechromes')) {
                $appScheme = 'googlechromes';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'slack')) {
                $appScheme = 'slack';
            } elseif (false !== \stripos($_SERVER['HTTP_USER_AGENT'], 'inapp')) {
                $appScheme = 'inapp';
            } else {
                $appScheme = '';
            }
        }

        return $appScheme;
    }

    public function getIp()
    {
        return \Limepie\getIp();
    }

    /**
     * Process a request header and return the one with best quality.
     */
    final protected function getBestQuality(array $qualityParts, ?string $name) : string
    {
        $i            = 0;
        $quality      = 0.0;
        $selectedName = '';

        foreach ($qualityParts as $accept) {
            if (0 === $i) {
                $quality      = (float) $accept['quality'];
                $selectedName = $accept[$name];
            } else {
                $acceptQuality = (float) $accept['quality'];

                if ($acceptQuality > $quality) {
                    $quality      = $acceptQuality;
                    $selectedName = $accept[$name];
                }
            }
            ++$i;
        }

        return $selectedName;
    }

    final protected function getQualityHeader(?string $serverIndex, ?string $name) : array
    {
        $returnedParts = [];
        $parts         = \preg_split('/,\s*/', (string) $this->getServer($serverIndex), -1, \PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            $headerParts = [];
            $tmp         = \preg_split('/\s*;\s*/', \trim($part), -1, \PREG_SPLIT_NO_EMPTY);

            foreach ($tmp as $headerPart) {
                if (false !== \strpos($headerPart, '=')) {
                    $split = \explode('=', $headerPart, 2);

                    if ('q' === $split[0]) {
                        $headerParts['quality'] = (float) $split[1];
                    } else {
                        $headerParts[$split[0]] = $split[1];
                    }
                } else {
                    $headerParts[$name]     = $headerPart;
                    $headerParts['quality'] = 1.0;
                }
            }

            $returnedParts[] = $headerParts;
        }

        return $returnedParts;
    }
}
