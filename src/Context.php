<?php

namespace Owl;

abstract class Context
{
    protected $config;

    abstract public function set($key, $val);
    abstract public function get($key = null);
    abstract public function has($key);
    abstract public function remove($key);
    abstract public function clear();

    public function __construct(array $config)
    {
        (new \Owl\Parameter\Validator())->execute($config, [
            'token' => ['type' => 'string'],
        ]);

        $this->config = $config;
    }

    public function setConfig($key, $val)
    {
        $this->config[$key] = $val;
    }

    public function getConfig($key = null)
    {
        return ($key === null)
             ? $this->config
             : isset($this->config[$key]) ? $this->config[$key] : null;
    }

    public function getToken()
    {
        return $this->getConfig('token');
    }

    // 保存上下文数据，根据需要重载
    public function save()
    {
    }

    public static function factory($type, array $config)
    {
        switch (strtolower($type)) {
            case 'session': return new \Owl\Context\Session($config);
            case 'cookie': return new \Owl\Context\Cookie($config);
            case 'redis': return new \Owl\Context\Redis($config);
            default:
                throw new \UnexpectedValueException('Unknown context handler type: '.$type);
        }
    }
}
