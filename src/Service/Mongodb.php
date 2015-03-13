<?php
namespace \Owl\Service;

if (!extension_loaded('mongo')) {
    throw new \Exception('Require "mongo" extension');
}

class Mongodb extends \Owl\Service {
    private $client;

    public function __construct(array $config = []) {
        parent::__construct(static::normalizeConfig($config));
    }

    public function __call($method, array $args) {
        return call_user_func_array([$this->connect(), $method], $args);
    }

    public function disconnect() {
        if ($this->isConnected()) {
            $this->client->close();
        }

        $this->client = null;
        return true;
    }

    public function connect() {
        if (!$this->client) {
            $this->client = new \MongoClient($this->getConfig('dsn'), $this->getConfig('options') ?: []);
        } else if (!$this->client->connected) {
            $this->client->connect();
        }

        return $this->client;
    }

    public function isConnected() {
        return $this->client && $this->client->connected;
    }

    /**
     * 格式化配置数据
     *
     * @param array $config
     * @static
     * @access public
     * @return array
     */
    static public function normalizeConfig(array $config) {
        if (!isset($config['dsn'])) {
            $config['dsn'] = sprintf('mongodb://%s:%s', ini_get('mongo.default_host'), ini_get('mongo.default_port'));
        }

        return $config;
    }
}
