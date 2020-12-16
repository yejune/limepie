<?php declare(strict_types=1);

namespace Limepie\Sso;

class Server
{
    public $brokers = [];

    public $brokerId;

    public $serverSessionId;

    public function getServerSessionId()
    {
        return $this->serverSessionId;
    }

    public function startBrokerSession($brokerSessionId)
    {
        \session_id($brokerSessionId);
        \session_start();

        //$this->brokerId = $this->validateBrokerAccessTokenId($serverSessionId);
    }

    public function getAttachChecksum($brokerId, $clientSessionId)
    {
        $broker = $this->getBrokerInfo($brokerId);

        if (false === isset($broker)) {
            return null;
        }

        return \hash('sha256', 'attach' . $clientSessionId . $broker['secret']);
    }

    public function startServerSession()
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            \session_start();
        }
        $this->serverSessionId = \session_id();
    }

    public function getBrokcerAccessTokenId($brokerId, $clientSessionId)
    {
        // broker secret을 가져와서 세션 아이디를 만든다.
        $broker = $this->getBrokerInfo($brokerId);

        if (false === isset($broker)) {
            return null;
        }

        return "SSO-{$brokerId}-{$clientSessionId}-" . \hash('sha256', 'session' . $clientSessionId . $broker['secret']);
    }

    public function getBrokerAccessTokenId()
    {
        if (!\function_exists('getallheaders')) {
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                if ('HTTP_' === \substr($name, 0, 5)) {
                    $headers[\str_replace(' ', '-', \ucwords(\strtolower(\str_replace('_', ' ', \substr($name, 5)))))] = $value;
                }
            }
        } else {
            $headers = \getallheaders();
        }

        if (true === isset($headers['Authorization']) && 0 === \strpos($headers['Authorization'], 'Bearer')) {
            $headers['Authorization'] = \substr($headers['Authorization'], 7);

            return $headers['Authorization'];
        }

        if (isset($_GET['access_token'])) {
            return $_GET['access_token'];
        }

        if (isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }

        if (isset($_GET['sso_session'])) {
            return $_GET['sso_session'];
        }

        return false;
    }

    protected function outputImage()
    {
        \header('Content-Type: image/png');
        echo \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=', true);
    }

    public function validateBrokerAccessTokenId($serverSessionId)
    {
        $matches = null;

        if (!\preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $this->getBrokerAccessTokenId(), $matches)) {
            return $this->fail('Invalid Acction Token id');
        }

        $brokerId = $matches[1];
        $brokerSessionId    = $matches[2];

        if ($this->getBrokcerAccessTokenId($brokerId, $brokerSessionId) !== $serverSessionId) {
            return $this->fail('Checksum failed: Client IP address may have changed', 403);
        }

        return $brokerId;
    }

    protected function getBrokerInfo($brokerId)
    {
        return isset($this->brokers[$brokerId]) ? $this->brokers[$brokerId] : null;
    }

    protected function fail($message, $http_status = 500)
    {
        // $this->returnType = $this->detectReturnType();

        // if (!empty($this->options['fail_exception'])) {
        //     throw new Exception($message, $http_status);
        // }

        // if (500 === $http_status) {
        //     \trigger_error($message, \E_USER_WARNING);
        // }

        // if ('jsonp' === $this->returnType) {
        //     echo $_REQUEST['callback'] . '(' . \json_encode(['error' => $message]) . ", ${http_status});";

        //     exit();
        // }

        // if ('redirect' === $this->returnType) {
        //     $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
        //     \header("Location: ${url}", true, 307);
        //     echo "You're being redirected to <a href='{$url}'>${url}</a>";

        //     exit();
        // }

        \http_response_code($http_status);
        \header('Content-type: application/json; charset=UTF-8');

        echo \json_encode(['error' =>'test '. $message]);

        exit();
    }
}
