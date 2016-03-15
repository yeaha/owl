<?php

namespace Owl\Service;

if (!extension_loaded('mongo')) {
    throw new \Exception('Require "mongo" extension');
}

class Mongodb extends \Owl\Service
{
    private $client;

    public function __construct(array $config = [])
    {
        parent::__construct(static::normalizeConfig($config));
    }

    public function __call($method, array $args)
    {
        return call_user_func_array([$this->connect(), $method], $args);
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->client->close();
        }

        $this->client = null;

        return true;
    }

    public function connect()
    {
        if (!$this->client) {
            $this->client = new \MongoClient($this->getConfig('dsn'), $this->getConfig('options') ?: []);
        } elseif (!$this->client->connected) {
            $this->client->connect();
        }

        return $this->client;
    }

    public function isConnected()
    {
        return $this->client && $this->client->connected;
    }

    /**
     * @example
     * $collection = $mongo->getCollection('db', 'collection');
     * $collection = $mongo->getCollection('db.collection');
     * $collection = $mongo->getCollection(['db', 'collection']);
     */
    public function getCollection($db, $collection = null)
    {
        if ($db instanceof \MongoCollection) {
            return $db;
        }

        if ($collection === null) {
            if (is_array($db)) {
                $collection = $db[1];
                $collection = $db[0];
            } else {
                $target = explode('.', $db, 2);
                list($db, $collection) = $target;
            }
        }

        return $this->selectCollection($db, $collection);
    }

    public function find($collection, array $query = [], array $fields = [])
    {
        return $this->getCollection($collection)->find($query, $fields);
    }

    public function findOne($collection, array $query = [], array $fields = [])
    {
        return $this->getCollection($collection)->findOne($query, $fields);
    }

    public function insert($collection, array $record, array $options = [])
    {
        return $this->getCollection($collection)->insert($record, $options);
    }

    public function save($collection, array $record, array $options = [])
    {
        return $this->getCollection($collection)->save($record, $options);
    }

    public function update($collection, array $criteria, array $record, array $options = [])
    {
        return $this->getCollection($collection)->update($criteria, $record, $options);
    }

    public function remove($collection, array $criteria, $options = [])
    {
        return $this->getCollection($collection)->remove($criteria, $options);
    }

    /**
     * 格式化配置数据.
     *
     * @param array $config
     * @static
     *
     * @return array
     */
    public static function normalizeConfig(array $config)
    {
        $config = array_merge([
            'dsn' => sprintf('mongodb://%s:%s', ini_get('mongo.default_host'), ini_get('mongo.default_port')),
        ], $config);

        if (!isset($config['options'])) {
            $config['options'] = [];
        }

        $config['options'] = array_merge([
            'connectTimeoutMS' => 3000,
            'socketTimeoutMS' => 3000,
        ], $config['options']);

        return $config;
    }
}
