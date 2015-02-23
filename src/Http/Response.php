<?php
namespace Owl\Http;

class Response {
    protected $end = false;
    protected $response;
    protected $body;
    protected $status = 200;
    protected $headers = [];
    protected $cookies = [];

    public function __construct($response) {
        $this->response = $response;
    }

    public function __get($key) {
        return $this->response->$key;
    }

    public function __set($key, $value) {
        $this->response->$key = $value;
    }

    public function __call($method, array $args) {
        return call_user_func_array(array($this->response, $method), $args);
    }

    public function getStatus() {
        return $this->status ?: 200;
    }

    public function setStatus($status) {
        $this->status = (int)$status;
        return $this;
    }

    public function setHeader($key, $value) {
        $this->headers[strtolower($key)] = $value;
        return $this;
    }

    public function setCookie() {
    }

    public function setBody($body) {
        $this->body = $body;
        return $this;
    }

    public function end($body = null) {
        if (!$this->end) {
            $this->send($body);
        }

        $this->end = true;
    }

    protected function send($body = null) {
        $response = $this->response;

        $status = $this->status;
        if ($status && $status !== 200) {
            $response->status($status);
        }

        // send header
        foreach ($this->headers as $key => $value) {
            $key = implode('-', array_map('ucfirst', explode('-', $key)));
            $response->header($key, $value);
        }

        if ($body === null) {
            $body = $this->body;
        }

        $response->end((string)$body);
    }
}
