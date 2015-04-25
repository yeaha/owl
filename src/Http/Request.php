<?php
namespace Owl\Http;

class Request {
    protected $get;
    protected $post;
    protected $cookies;
    protected $headers;
    protected $server;
    protected $parameters;
    protected $method;

    public function __construct() {
        $this->reset();
    }

    public function reset() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->headers = null;
        $this->parameters = [];
        $this->method = null;
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

    public function getIP($proxy = null) {
        $ip = $proxy
            ? $this->getServer('http_x_forwarded_for') ?: $this->getServer('remote_addr')
            : $this->getServer('remote_addr');

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

    public function getMethod() {
        if ($this->method) {
            return $this->method;
        }

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

        return $this->method = strtoupper($method);
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

    public function isAjax() {
        $val = $this->getHeader('x-requested-with');
        return $val && (strtolower($val) === 'xmlhttprequest');
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

        self::resetServer();

        $_SERVER['REQUEST_METHOD'] = strtoupper($options['method']);
        $_SERVER['REQUEST_URI'] = $options['uri'];

        if ($options['ip']) {
            $_SERVER['REMOTE_ADDR'] = $options['ip'];
        }

        if ($query = parse_url($options['uri'], PHP_URL_QUERY)) {
            parse_str($query, $get);
            $options['get'] = array_merge($get, $options['get']);
        }

        $_COOKIE = $options['cookies'];
        $_GET = $options['get'];
        $_POST = $options['post'];
        $_REQUEST = array_merge($_GET, $_POST);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_POST = [];
        }

        foreach ($options['headers'] as $key => $value) {
            $key = 'HTTP_'. strtoupper(str_replace('-', '_', $key));
            $_SERVER[$key] = $value;
        }

        return new Request;
    }

    static private function resetServer() {
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                unset($_SERVER[$key]);
            }
        }
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }
}
