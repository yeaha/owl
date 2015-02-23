<?php
namespace Owl\Http;

class Request {
    protected $request;

    public function __construct($request) {
        $this->request = $request;
    }

    public function __get($key) {
        return $this->request->$key;
    }

    public function __call($method, array $args) {
        return call_user_func_array(array($this->request, $method), $args);
    }

    public function getHeader($key) {
        $key = strtolower($key);
        return isset($this->request->header[$key]) ? $this->request->header[$key] : false;
    }

    public function getHeaders() {
        return isset($this->request->header) ? $this->request->header : [];
    }

    public function getCookie($key) {
        return isset($this->request->cookie[$key]) ? $this->request->cookie[$key] : false;
    }

    public function getCookies() {
        return isset($this->request->cookie) ? $this->request->cookie : [];
    }

    public function getRequestURI() {
        return isset($this->request->server['REQUEST_URI'])
             ? $this->request->server['REQUEST_URI']
             : '/';
    }

    public function getRequestPath() {
        return parse_url($this->getRequestURI(), PHP_URL_PATH);
    }

    public function getExtension() {
        return pathinfo($this->getRequestPath(), PATHINFO_EXTENSION) ?: 'html';
    }

    public function getMethod() {
        $method = strtoupper($this->request->server['REQUEST_METHOD']);

        if ($method === 'POST' && $override = $this->getHeader('x-http-method-override')) {
            $method = strtoupper($override);
        }

        return $method;
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
