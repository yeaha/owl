<?php

namespace Owl\Swoole;

class Request extends \Owl\Http\Request
{
    protected $swoole_request;

    public function __construct($swoole_request)
    {
        $this->swoole_request = $swoole_request;

        $get = isset($swoole_request->get) ? $swoole_request->get : [];
        $post = isset($swoole_request->post) ? $swoole_request->post : [];
        $cookies = isset($swoole_request->cookie) ? $swoole_request->cookie : [];
        $server = isset($swoole_request->server) ? array_change_key_case($swoole_request->server, CASE_UPPER) : [];
        $headers = isset($swoole_request->header) ? array_change_key_case($swoole_request->header, CASE_LOWER) : [];
        $files = isset($swoole_request->files) ? $swoole_request->files : [];

        foreach ($headers as $key => $value) {
            $key = 'HTTP_'.strtoupper(str_replace('-', '_', $key));
            $server[$key] = $value;
        }

        $_GET = $get;
        $_POST = $post;
        $_COOKIE = $cookies;
        $_SERVER = $server;
        $_FILES = $files;

        parent::__construct($get, $post, $server, $cookies, $files);
    }
}
