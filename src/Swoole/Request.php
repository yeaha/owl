<?php
namespace Owl\Swoole;

class Request extends \Owl\Http\Request {
    protected $request;

    public function __construct($request) {
        $this->request = $request;
        parent::__construct();
    }

    public function reset() {
        $request = $this->request;

        $this->get = isset($request->get) ? $request->get : [];
        $this->post = isset($request->post) ? $request->post : [];
        $this->cookies = isset($request->cookie) ? $request->cookie : [];
        $this->server = isset($request->server) ? $request->server : [];
        $this->headers = isset($request->header) ? $request->header : [];
    }
}
