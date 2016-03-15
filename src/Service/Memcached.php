<?php

namespace Owl\Service;

if (!extension_loaded('memcached')) {
    throw new \RuntimeException('Require "memcached" extension!');
}

/**
 * @example
 * $config = [
 *     'persistent_id' => 'foobar',     // optional
 *     'servers' => [
 *         ['192.168.1.2', 11211, 2],
 *         ['192.168.1.3', 11211, 1],
 *     ],
 *     'options' => [                   // optional
 *         \Memcached::OPT_PREFIX_KEY => 'widgets',
 *     ],
 * ];
 *
 * $memcached = new \Owl\Service\Memcached($config);
 */
class Memcached extends \Owl\Service
{
    protected $memcached;

    public function __call($method, array $args)
    {
        $memcached = $this->connect();

        return $args
             ? call_user_func_array([$memcached, $method], $args)
             : $memcached->$method();
    }

    public function connect()
    {
        if ($this->memcached) {
            return $this->memcached;
        }

        $memcached = new \Memcached($this->getConfig('persistent_id'));

        $servers = $this->getConfig('servers') ?: [['127.0.0.1', 11211]];

        if (!$memcached->addServers($servers)) {
            throw new \Owl\Service\Exception('Memcached connect failed!');
        }

        if ($options = $this->getConfig('options')) {
            $memcached->setOptions($options);
        }

        return $this->memcached = $memcached;
    }

    public function disconnect()
    {
        if ($this->memcached) {
            $this->memcached->quit();
            $this->memcached = null;
        }
    }
}
