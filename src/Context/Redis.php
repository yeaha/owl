<?php
namespace Owl\Context;

/**
 * @example
 * $config = array(
 *     'token' => (string),                 // 必须，上下文存储唯一标识
 *     'service' => (\Owl\Service\Redis),   // 必须，用于存储的redis服务名
 *     'ttl' => (integer),                  // 可选，生存期，单位：秒，默认：0
 * );
 *
 * $context = new \Owl\Context\Redis($config);
 */
class Redis extends \Owl\Context\Adapter {
    protected $data;
    protected $saved_keys;
    protected $dirty = false;

    public function __construct(array $config) {
        parent::__construct($config);

        $redis = $this->getService();
        $token = $this->getToken();

        $this->data = $redis->hGetAll($token) ?: array();
        $this->saved_keys = array_keys($this->data);
    }

    public function __destruct() {
        $this->save();
    }

    public function set($key, $val) {
        if (isset($this->data[$key]) && $this->data[$key] === $val) {
            return true;
        }

        $this->data[$key] = $val;
        $this->dirty = true;
    }

    public function get($key = null) {
        if ($key === null) {
            return $this->data;
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function has($key) {
        return isset($this->data[$key]);
    }

    public function remove($key) {
        if (!isset($this->data[$key])) {
            return false;
        }

        unset($this->data[$key]);
        $this->dirty = true;
    }

    public function clear() {
        $this->data = [];
        $this->dirty = true;
    }

    public function setTimeout($ttl) {
        $redis = $this->getService();
        $token = $this->getToken();

        return $redis->setTimeout($token, $ttl);
    }

    public function save() {
        if (!$this->dirty) {
            return true;
        }

        $this->dirty = false;

        $redis = $this->getService();
        $token = $this->getToken();

        if (!$data = $this->data) {
            $redis->delete($token);
            $this->saved_keys = [];
            return true;
        }

        $removed_keys = array_diff($this->saved_keys, array_keys($data));

        $redis->multi(\Redis::PIPELINE);

        foreach ($removed_keys as $key) {
            $redis->hDel($token, $key);
        }

        $redis->hMSet($token, $data);

        if ($ttl = (int)$this->getConfig('ttl')) {
            $redis->setTimeout($token, $ttl);
        }

        $redis->exec();

        $this->saved_keys = array_keys($data);
        return true;
    }

    protected function getService() {
        $service = $this->getConfig('service');

        if (!$service || !($service instanceof \Owl\Service\Redis)) {
            throw new \Exception('Invalid redis service.');
        }

        return $service;
    }
}
