<?php declare(strict_types=1);

namespace Limepie\Sso;

/**
 * Single sign-on broker.
 *
 * @see https://github.com/legalthings/sso
 *
 * The broker lives on the website visited by the user. The broken doesn't have any user credentials stored. Instead it
 * will talk to the SSO server in name of the user, verifying credentials and getting user information.
 */
class Broker
{
    public $broker;

    public $brokerSessionId;

    public $url;

    public $secret;

    public function __construct($url, $broker, $secret, $cookie_lifetime = 3600)
    {
        if (!$url) {
            throw new \InvalidArgumentException('SSO server URL not specified');
        }

        if (!$broker) {
            throw new \InvalidArgumentException('SSO broker id not specified');
        }

        if (!$secret) {
            throw new \InvalidArgumentException('SSO broker secret not specified');
        }
        $this->url    = $url;
        $this->broker = $broker;
        $this->secret = $secret;

        if (false === isset($_SESSION)) {
            \session_start();
        }
        $this->brokerSessionId = \session_id();
    }

    // public function __call($fn, $args)
    // {
    //     $sentence = \strtolower(\preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $fn));
    //     $parts    = \explode(' ', $sentence);

    //     $method = 1 < \count($parts) && \in_array(\strtoupper($parts[0]), ['GET', 'DELETE'], true)
    //         ? \strtoupper(\array_shift($parts))
    //         : 'POST';
    //     $command = \implode('-', $parts);

    //     return $this->request($method, $command, $args);
    // }

    public function getAttachUrl($params = [])
    {
        $data = [
            'command'    => 'attach',
            'broker'     => $this->broker,
            'session_id' => $this->brokerSessionId,
            'checksum'   => \hash('sha256', 'attach' . $this->brokerSessionId . $this->secret),
        ] + $_GET;

        return $this->url . '?' . \http_build_query($data + $params);
    }

    public function getPingUrl($params = [])
    {
        $data = [
            'command'    => 'attach',
            'broker'     => $this->broker,
            'session_id' => $this->brokerSessionId,
            'checksum'   => \hash('sha256', 'attach' . $this->brokerSessionId . $this->secret),
        ] + $_GET;

        return $this->url . '/ping' . '?' . \http_build_query($data + $params);
    }


    public function ping($returnUrl = null)
    {
        return $this->request('GET', '/ping', 'ping')['result'] ?? '';
    }
    public function run() {
        return $this->request('GET', '/command', 'ping');
    }
    public function attach($returnUrl = null)
    {
        if (true === $returnUrl) {
            $protocol  = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $returnUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        $params = ['return_url' => $returnUrl];
        $url    = $this->getAttachUrl($params);

        \header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        \header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        \header("Location: ${url}", true, 307);
        echo "You're redirected to <a href='${url}'>${url}</a>";

        exit();
    }

    /**
     * Generate session id from session key
     *
     * @return string
     */
    protected function getAccessTokenId()
    {
        $checksum = \hash('sha256', 'session' . $this->brokerSessionId . $this->secret);

        return "SSO-{$this->broker}-{$this->brokerSessionId}-${checksum}";
    }

    /**
     * Get the request url for a command
     *
     * @param string $command
     * @param array  $params  Query parameters
     *
     * @return string
     */
    protected function getRequestUrl($command, $path = '/', $params = [])
    {
        $params['command'] = $command;

        return $this->url .$path. '?' . \http_build_query($params);
    }

    /**
     * Execute on SSO server.
     *
     * @param string       $method  HTTP method: 'GET', 'POST', 'DELETE'
     * @param string       $command Command
     * @param array|string $data    Query or post parameters
     *
     * @return array|object
     */
    public function request($method, $path, $command, $data = null)
    {
        $url = $this->getRequestUrl($command, $path, !$data || 'POST' === $method ? [] : $data);
        $ch = \curl_init($url);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $this->getAccessTokenId()]);

        if ('POST' === $method && !empty($data)) {
            $post = \is_string($data) ? $data : \http_build_query($data);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        }

        $response = \curl_exec($ch);

        if (0 !== \curl_errno($ch)) {
            $message = 'Server request failed: ' . \curl_error($ch);

            throw new Exception($message);
        }

        $httpCode      = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        [$contentType] = \explode(';', \curl_getinfo($ch, \CURLINFO_CONTENT_TYPE));

        if ('application/json' !== $contentType) {
            $message = 'Expected application/json response, got ' . $contentType;

            throw new \Exception($message);
        }

        $data = \json_decode($response, true);

        if (403 === $httpCode) {
            //$this->clearToken();

            throw new \Limepie\Sso\NotAttachedException($data['error'] ?: $response, $httpCode);
        }

        if (400 <= $httpCode) {
            throw new \Exception($data['error'] ?: $response, $httpCode);
        }

        return $data['payload'] ?? '';
    }
}
