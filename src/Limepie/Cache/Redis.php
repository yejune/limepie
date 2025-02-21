<?php declare(strict_types=1);

namespace Limepie\Cache;

use Limepie\Di;

class Redis
{
    private $redis;

    public function __construct(string $dsn, array $options = [], float $timeout = 5.0)
    {
        if (\strpos($dsn, '://')) {
            $parsedUrl = \parse_url($dsn);
            $host      = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $port      = $parsedUrl['port'] ?? 6379; // 기본 포트 6379 사용
        } else {
            [$host, $port] = \explode(':', $dsn);
        }

        $this->redis = new \Redis();
        $useTLS      = \str_starts_with($host, 'tls://');

        try {
            if ($useTLS) {
                $defaultTLSOptions = [
                    'stream' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $options = \array_merge($defaultTLSOptions, $options);
            }
            $this->redis->connect(
                $host,
                (int) $port,
                $timeout,
                null,
                0,
                0,
                $options
            );
        } catch (\Throwable $e) {
            throw new \Exception('Redis 연결 실패: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getDomainKey(string $key)
    {
        return Di::getRequest()->getHost() . ':' . $key;
    }

    /**
     * Set a value in the cache.
     *
     * @param string $key   the key under which to store the value
     * @param mixed  $value the value to store
     * @param int    $ttl   The TTL in seconds. Use 0 or negative for non-expiring keys.
     *
     * @return bool true on success, false on failure
     */
    public function set(string $key, $value, int $ttl = 0) : bool
    {
        $storedValue = \is_int($value) ? $value : $this->serialize($value);

        if ($ttl <= 0) {
            return $this->redis->set($key, $storedValue);
        }

        return $this->redis->setex($key, $ttl, $storedValue);
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key the key of the value to retrieve
     *
     * @return mixed the value associated with the key, or false if not found
     */
    public function get(string $key)
    {
        $value = $this->redis->get($this->getDomainKey($key));

        if (false === $value) {
            return false;
        }

        return \is_numeric($value) ? (int) $value : $this->unserialize($value);
    }

    /**
     * Delete a value from the cache.
     *
     * @param string $key the key of the value to delete
     *
     * @return bool true if the key was deleted, false otherwise
     */
    public function delete(string $key) : bool
    {
        if (\str_ends_with($key, '*')) {
            // prefix로 끝나는 경우 scan으로 삭제
            $this->deleteScan($this->getDomainKey($key));

            return true;
        }

        // 단일 키 삭제
        return $this->redis->del($this->getDomainKey($key)) > 0;
    }

    public function deleteScan(string $prefix)
    {
        $iterator = null;

        do {
            // SCAN으로 키들을 가져오기 (한 번에 100개씩)
            $keys = $this->redis->scan($iterator, $prefix, 100);

            // 찾은 키들이 있다면 삭제
            if ($keys) {
                $this->redis->del($keys);
            }
        } while ($iterator > 0);  // iterator가 0이 될 때까지 계속 스캔
    }

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key the key to check
     *
     * @return bool true if the key exists, false otherwise
     */
    public function exists(string $key) : bool
    {
        return $this->redis->exists($this->getDomainKey($key)) > 0;
    }

    /**
     * Increment a value in the cache.
     *
     * @param string $key   the key of the value to increment
     * @param int    $value the amount to increment by
     *
     * @return int the new value after incrementing
     */
    public function increment(string $key, int $value = 1) : int
    {
        return $this->redis->incrBy($key, $value);
    }

    /**
     * Decrement a value in the cache.
     *
     * @param string $key   the key of the value to decrement
     * @param int    $value the amount to decrement by
     *
     * @return int the new value after decrementing
     */
    public function decrement(string $key, int $value = 1) : int
    {
        return $this->redis->decrBy($key, $value);
    }

    /**
     * Flush all keys from the cache.
     *
     * @return bool true on success, false on failure
     */
    public function flush() : bool
    {
        return $this->redis->flushAll();
    }

    /**
     * Serialize data for storage.
     *
     * @param mixed $data the data to serialize
     *
     * @return string the serialized data
     */
    private function serialize($data)
    {
        return \serialize($data);
    }

    /**
     * Unserialize data from storage.
     *
     * @param string $data the data to unserialize
     *
     * @return mixed the unserialized data
     */
    private function unserialize($data)
    {
        return \unserialize($data);
    }

    public function info()
    {
        return $this->redis->info();
    }

    public function ping()
    {
        return $this->redis->ping();
    }
}
