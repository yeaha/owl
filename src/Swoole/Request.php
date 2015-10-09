<?php
namespace Owl\Swoole;

class Request extends \Owl\Http\Request {
    protected $swoole_request;

    public function __construct($swoole_request) {
        $this->swoole_request = $swoole_request;

        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookies = isset($request->cookie) ? $request->cookie : [];
        $server = isset($request->server) ? array_change_key_case($request->server, CASE_UPPER) : [];
        $headers = isset($request->header) ? array_change_key_case($request->header, CASE_LOWER) : [];
        $files = isset($request->files) ? $request->files : [];

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
