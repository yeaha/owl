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

        $body = $this->body;

        if ($body instanceof \Closure) {
            ob_start(function($buffer) use ($response) {
                $response->write($buffer);
            }, 8192);
            call_user_func($body);
            $response->write(ob_get_clean());
            $response->end();
        } else {
            $response->end((string)$body);
        }
    }
}
