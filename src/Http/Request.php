<?php
namespace Owl\Http;

class Request {
    protected $get;
    protected $post;
    protected $cookies;
    protected $headers;
    protected $server;

    public function __construct() {
        $this->reset();
    }

    public function reset() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->headers = null;
    }

    public function get($key = null) {
        if ($key === null) {
            return $this->get;
        }

        return isset($this->get[$key]) ? $this->get[$key] : null;
    }

    public function post($key = null) {
        if ($key === null) {
            return $this->post;
        }

        return isset($this->post[$key]) ? $this->post[$key] : null;
    }

    public function hasGet($key) {
        return array_key_exists($key, $this->get);
    }

    public function hasPost($key) {
        return array_key_exists($key, $this->post);
    }

    public function getServer($key = null) {
        if ($key === null) {
            return $this->server;
        }

        $key = strtoupper($key);
        return isset($this->server[$key]) ? $this->server[$key] : false;
    }

    public function getHeader($key) {
        $key = strtolower($key);
        $headers = $this->getHeaders();
        return isset($headers[$key]) ? $headers[$key] : false;
    }

    public function getHeaders() {
        if ($this->headers !== null) {
            return $this->headers;
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            $pos = strpos($key, 'HTTP_');

            if ($pos !== false) {
                $key = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = $value;
            }
        }

        return $this->headers = $headers;
    }

    public function getCookie($key) {
        return isset($this->cookies[$key]) ? $this->cookies[$key] : false;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function getRequestURI() {
        return isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '/';
    }

    public function getRequestPath() {
        return parse_url($this->getRequestURI(), PHP_URL_PATH);
    }

    public function getExtension() {
        return pathinfo($this->getRequestPath(), PATHINFO_EXTENSION) ?: 'html';
    }

    public function getMethod() {
        $method = strtoupper($this->getServer('REQUEST_METHOD'));

        if ($method !== 'POST') {
            return $method;
        }

        if ($override = $this->getHeader('x-http-method-override')) {
            $method = $override;
        } elseif ($_method = $this->post('_method')) {
            unset($this->post['_method']);
            $method = $_method;
        }

        return strtoupper($method);
    }

    public function isGet() {
        return $this->getMethod() === 'GET';
    }

    public function isPost() {
        return $this->getMethod() === 'POST';
    }

    public function isPut() {
        return $this->getMethod() === 'PUT';
    }

    public function isDelete() {
        return $this->getMethod() === 'DELETE';
    }
}
