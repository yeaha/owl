<?php

namespace Owl;

abstract class Service
{
    protected $config = [];

    abstract public function disconnect();

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return isset($this->config[$key])
             ? $this->config[$key]
             : false;
    }
}
