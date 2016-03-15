<?php

namespace Tests\Mock\Context;

class Redis extends \Owl\Context\Redis
{
    public function isDirty()
    {
        return $this->dirty;
    }

    public function getTimeout()
    {
        $redis = $this->getService();
        $token = $this->getToken();

        return $redis->ttl($token);
    }
}
