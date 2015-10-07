<?php
namespace Owl\Http;

use Psr\Http\Message\UriInterface;

trait RequestTrait {
    use \Owl\Http\MessageTrait;

    protected $method;
    protected $server = [];

    public function getRequestTarget() {
    }

    public function withRequestTarget($requestTarget) {
    }

    public function getMethod() {
        if ($this->method !== null) {
            return $this->method;
        }

        $method = isset($this->server['REQUEST_METHOD']) ? strtoupper($this->server['REQUEST_METHOD']) : 'GET';
        if ($method !== 'POST') {
            return $this->method = $method;
        }

        $override = $this->getHeader('x-http-method-override');
    }

    public function withMethod($method) {
        $result = clone $this;
        $result->method = strtoupper($method);

        return $result;
    }

    public function getUri() {
    }

    public function withUri(UriInterface $uri, $preserveHost = false) {
    }
}
