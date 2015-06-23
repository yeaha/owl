<?php
namespace Owl\Http;

class Response {
    protected $body;
    protected $cookies = [];
    protected $end = false;
    protected $headers = [];
    protected $status = 200;
    protected $parameters = [];

    public function setStatus($status) {
        $this->status = (int)$status;
        return $this;
    }

    public function getStatus() {
        return $this->status ?: 200;
    }

    public function setHeader($key, $value) {
        $key = implode('-', array_map('ucfirst', explode('-', $key)));
        $this->headers[$key] = $value;
        return $this;
    }

    public function getHeader($key) {
        $key = implode('-', array_map('ucfirst', explode('-', $key)));

        return isset($this->headers[$key]) ? $this->headers[$key] : false;
    }

    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = null, $httponly = true) {
        if ($secure === null) {
            $secure = isset($_SERVER['HTTPS']) ? (bool)$_SERVER['HTTPS'] : false;
        }

        $key = sprintf('%s@%s:%s', $name, $domain, $path);
        $this->cookies[$key] = [$name, $value, $expire, $path, $domain, $secure, $httponly];
        return $this;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function setBody($body) {
        $this->body = $body;
        return $this;
    }

    public function getBody() {
        return $this->body;
    }

    public function redirect($url, $status = 303) {
        $this->setHeader('Location', $url);
        $this->setStatus($status);

        return $this;
    }

    public function end() {
        if (!$this->end) {
            $this->send();
            $this->end = true;
        }
    }

    public function reset() {
        $this->body = null;
        $this->cookies = [];
        $this->end = false;
        $this->headers = [];
        $this->status = 200;
        $this->parameters = [];
    }

    /**
     * 设置自定义参数
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParameter($key, $value) {
        $this->parameters[$key] = $value;
        return $this;
    }

    /**
     * 获取自定义参数
     *
     * @param string $key
     * @return mixed|false
     */
    public function getParameter($key) {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : false;
    }

    public function getParameters() {
        return $this->parameters;
    }

    protected function send() {
        if (!headers_sent()) {
            $status = $this->getStatus();
            if ($status !== 200) {
                header(sprintf('HTTP/1.1 %d %s', $status, \Owl\Http::getStatusMessage($status)));
            }

            foreach ($this->headers as $key => $value) {
                header(sprintf('%s: %s', $key, $value));
            }
            $this->headers = [];

            foreach ($this->cookies as $config) {
                list($name, $value, $expire, $path, $domain, $secure, $httponly) = $config;
                setCookie($name, $value, $expire, $path, $domain, $secure, $httponly);
            }
            $this->cookie = [];
        }

        if (isset($_SESSION) && $_SESSION instanceof \Owl\Session) {
            $_SESSION->commit();
        }

        $body = $this->body;
        if (in_array($this->getStatus(), [204, 304])) {
            $body = '';
        }

        if ($body instanceof \Closure) {
            echo call_user_func($body);
        } else {
            echo (string)$body;
        }
    }
}
