<?php
namespace Tests\Mock;

class Response extends \Owl\Http\Response {
    public function __construct() {
        $this->cookies = \Tests\Mock\Cookie::getInstance();
    }

    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true) {
        call_user_func_array([$this->cookies, 'set'], func_get_args());
    }

    public function getCookies($path = '/') {
        return $this->cookies->get($path);
    }

    public function getCookie($key, $path = '/') {
        $cookies = $this->cookies->get($path);
        return isset($cookies[$key]) ? $cookies[$key] : false;
    }
}
