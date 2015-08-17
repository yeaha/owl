<?php
namespace Owl\Service;

// https://github.com/nrk/predis
if (!class_exists('\Predis\Client')) {
    throw new \Exception('Require Predis library');
}

/**
 * @example
 * $parameters = [
 *     'scheme' => 'tcp',
 *     'host' => '127.0.0.1',
 *     'port' => 6379,
 *     'database' => 1,
 *     'persistent' => true,
 *     'timeout' => 3.0,
 * ];
 *
 * $options = [
 *     'exception' => true,
 * ];
 *
 * $redis = new \Owl\Service\Predis($parameters, $options);
 */
class Predis extends \Owl\Service {
    protected $client;

    protected $command_alias = [
        'settimeout' => 'expire',
        'delete' => 'del',
    ];

    public function __call($method, array $args) {
        $client = $this->connect();

        $command = strtolower($method);
        if (isset($this->command_alias[$command])) {
            $method = $this->command_alias[$command];
        }

        return $args ? call_user_func_array([$client, $method], $args) : $client->$method();
    }

    public function connect() {
        if (!$this->client || !$this->client->isConnected()) {
            $parameters = $this->getConfig('parameters');
            $options = $this->getConfig('options') ?: [];

            $this->client = new \Predis\Client($parameters, $options);
        }

        return $this->client;
    }

    public function disconnect() {
        if ($this->client) {
            $this->client->disconnect();
            $this->client = null;
        }
    }

    public function multi() {
        return $this->connect()->transaction();
    }

    public function hMGet($key, array $fields) {
        $redis = $this->connect();

        $values = $redis->hmget($key, $fields);

        $result = [];
        foreach ($values as $i => $value) {
            $key = $fields[$i];

            $result[$key] = $value;
        }

        return $result;
    }
}
