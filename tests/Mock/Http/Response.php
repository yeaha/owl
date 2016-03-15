<?php

namespace Tests\Mock\Http;

class Response extends \Owl\Http\Response
{
    public function __construct()
    {
        $this->cookies = \Tests\Mock\Cookie::getInstance();
    }

    public function withCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true)
    {
        call_user_func_array([$this->cookies, 'set'], func_get_args());
    }

    public function getCookies()
    {
        return $this->cookies->get();
    }
}
