<?php
namespace Owl\Swoole;

class Response extends \Owl\Http\Response {
    public function __construct($response) {
        $this->response = $response;
    }

    protected function send() {
        $response = $this->response;

        $status = $this->getStatus();
        if ($status && $status !== 200) {
            $response->status($status);
        }

        foreach ($this->headers as $key => $value) {
            $response->header($key, $value);
        }

        foreach ($this->cookies as list($name, $value, $expire, $path, $domain, $secure, $httponly)) {
            $response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }

        $response->end($this->body);
    }
}
