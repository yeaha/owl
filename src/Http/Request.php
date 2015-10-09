<?php
namespace Owl\Http;

use \Owl\Http\ResourceStream;
use \Owl\Http\Uri;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\UriInterface;

class Request implements ServerRequestInterface {
    use \Owl\Http\MessageTrait;

    protected $get;
    protected $post;
    protected $cookies;
    protected $files;
    protected $method;
    protected $uri;

    public function __construct($get = null, $post = null, $server = null, $cookies = null, $files = null) {
        $this->get = $get === null ? $_GET : $get;
        $this->post = $post === null ? $_POST : $post;
        $this->server = $server === null ? $_SERVER : $server;
        $this->cookies = $cookies === null ? $_COOKIE : $cookies;
        $this->files = $files === null ? $_FILES : $files;

        $this->initialize();
    }

    public function __clone() {
        $this->method = null;
        $this->uri = null;
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

    public function getRequestTarget() {
        return isset($this->server['REQUEST_URI']) ? $this->server['REQUEST_URI'] : '/';
    }

    public function withRequestTarget($requestTarget) {
        $result = clone $this;

        $result->server['REQUEST_URI'] = $requestTarget;

        return $result;
    }

    public function getMethod() {
        if ($this->method !== null) {
            return $this->method;
        }

        $method = isset($this->server['REQUEST_METHOD']) ? strtoupper($this->server['REQUEST_METHOD']) : 'GET';
        if ($method !== 'POST') {
            return $this->method = $method;
        }

        $override = $this->getHeader('x-http-method-override') ?: $this->post('_method');
        if ($override) {
            if (is_array($override)) {
                $override = array_unshift($override);
            }

            $method = $override;
        }

        return $this->method = strtoupper($method);
    }

    public function withMethod($method) {
        $result = clone $this;
        $result->method = strtoupper($method);

        return $result;
    }

    public function getUri() {
        if ($this->uri) {
            return $this->uri;
        }

        $scheme = $this->getServerParam('HTTPS') ? 'https' : 'http';
        $user = $this->getServerParam('PHP_AUTH_USER');
        $password = $this->getServerParam('PHP_AUTH_PW');
        $host = $this->getServerParam('SERVER_NAME') ?: $this->getServerParam('SERVER_ADDR') ?: '127.0.0.1';
        $port = $this->getServerParam('SERVER_PORT');

        return $this->uri = (new Uri($this->getRequestTarget()))
                            ->withScheme($scheme)
                            ->withUserInfo($user, $password)
                            ->withHost($host)
                            ->withPort($port);
    }

    public function withUri(UriInterface $uri, $preserveHost = false) {
        throw new \Exception('Request::withUri() not implemented');
    }

    public function getServerParams() {
        return $this->server;
    }

    public function getServerParam($name) {
        $name = strtoupper($name);

        return isset($this->server[$name]) ? $this->server[$name] : false;
    }

    public function getCookieParams() {
        return $this->cookies;
    }

    public function getCookieParam($name) {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : false;
    }

    public function withCookieParams(array $cookies) {
        $result = clone $this;

        $result->cookies = $cookies;

        return $result;
    }

    public function getQueryParams() {
        return $this->get;
    }

    public function withQueryParams(array $query) {
        $result = clone $this;

        $result->get = $query;

        return $result;
    }

    public function getUploadedFiles() {
        throw new \Exception('Request::getUploadedFiles() not implemented');
    }

    public function withUploadedFiles(array $uploadFiles) {
        throw new \Exception('Request::withUploadedFiles() not implemented');
    }

    public function getParsedBody() {
        throw new \Exception('Request::getParsedBody() not implemented');
    }

    public function withParsedBody($data) {
        throw new \Exception('Request::withParsedBody() not implemented');
    }

    public function getClientIP($proxy = null) {
        $ip = $proxy
            ? $this->getServerParam('http_x_forwarded_for') ?: $this->getServerParam('remote_addr')
            : $this->getServerParam('remote_addr');

        if (strpos($ip, ',') === false) {
            return $ip;
        }

        // private ip range, ip2long()
        $private = array(
            array(0, 50331647),             // 0.0.0.0, 2.255.255.255
            array(167772160, 184549375),    // 10.0.0.0, 10.255.255.255
            array(2130706432, 2147483647),  // 127.0.0.0, 127.255.255.255
            array(2851995648, 2852061183),  // 169.254.0.0, 169.254.255.255
            array(2886729728, 2887778303),  // 172.16.0.0, 172.31.255.255
            array(3221225984, 3221226239),  // 192.0.2.0, 192.0.2.255
            array(3232235520, 3232301055),  // 192.168.0.0, 192.168.255.255
            array(4294967040, 4294967295),  // 255.255.255.0 255.255.255.255
        );

        $ip_set = array_map('trim', explode(',', $ip));

        // 检查是否私有地址，如果不是就直接返回
        foreach ($ip_set as $key => $ip) {
            $long = ip2long($ip);

            if ($long === false) {
                unset($ip_set[$key]);
                continue;
            }

            $is_private = false;

            foreach ($private as $m) {
                list($min, $max) = $m;
                if ($long >= $min && $long <= $max) {
                    $is_private = true;
                    break;
                }
            }

            if (!$is_private) return $ip;
        }

        return array_shift($ip_set) ?: '0.0.0.0';
    }

    public function isGet() {
        return $this->getMethod() === 'GET' || $this->getMethod() === 'HEAD';
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

    public function isAjax() {
        $val = $this->getHeader('x-requested-with');
        return $val && (strtolower($val[0]) === 'xmlhttprequest');
    }

    protected function initialize() {
        $this->body = new ResourceStream(fopen('php://input', 'r'));

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$key] = explode(',', $value);
            }
        }
        $this->headers = $headers;
    }

    /**
     * 构造http请求对象，供测试使用
     *
     * @example
     * $request = Request::factory([
     *     'uri' => '/',
     *     'method' => 'post',
     *     'cookies' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'headers' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'get' => [
     *         $key => $value,
     *         ...
     *     ],
     *     'post' => [
     *         $key => $value,
     *         ...
     *     ],
     * ]);
     */
    static public function factory(array $options = []) {
        $options = array_merge([
            'uri' => '/',
            'method' => 'GET',
            'cookies' => [],
            'headers' => [],
            'get' => [],
            'post' => [],
            'ip' => '',
        ], $options);

        $server = [];
        $server['REQUEST_METHOD'] = strtoupper($options['method']);
        $server['REQUEST_URI'] = $options['uri'];

        if ($options['ip']) {
            $server['REMOTE_ADDR'] = $options['ip'];
        }

        if ($query = parse_url($options['uri'], PHP_URL_QUERY)) {
            parse_str($query, $get);
            $options['get'] = array_merge($get, $options['get']);
        }

        $cookies = $options['cookies'];
        $get = $options['get'];
        $post = $options['post'];

        if ($server['REQUEST_METHOD'] === 'GET') {
            $post = [];
        }

        foreach ($options['headers'] as $key => $value) {
            $key = 'HTTP_'. strtoupper(str_replace('-', '_', $key));
            $server[$key] = $value;
        }

        return new Request($get, $post, $server, $cookies);
    }

    /**
     * @deprecated
     */
    public function getRequestURI() {
        return $this->getRequestTarget();
    }

    /**
     * @deprecated
     */
    public function getRequestPath() {
        return $this->getUri()->getPath();
    }

    /**
     * @deprecated
     */
    public function getExtension() {
        return $this->getUri()->getExtension();
    }

    /**
     * @deprecated
     */
    public function setParameter($key, $value) {
        return $this->withAttribute($key, $value);
    }

    /**
     * @deprecated
     */
    public function getParameter($key) {
        return $this->getAttribute($key);
    }

    /**
     * @deprecated
     */
    public function getParameters() {
        return $this->getAttributes();
    }

    /**
     * @deprecated
     */
    public function getServer($key = null) {
        if ($key === null) {
            return $this->getServerParams();
        }

        return $this->getServerParam($key);
    }

    /**
     * @deprecated
     */
    public function getCookie($key) {
        return $this->getCookieParam($key);
    }

    /**
     * @deprecated
     */
    public function getCookies() {
        return $this->getCookieParams();
    }

    /**
     * @deprecated
     */
    public function getIP($proxy = null) {
        return $this->getClientIP($proxy);
    }
}
