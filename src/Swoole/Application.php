<?php
namespace Owl\Swoole;

class Application extends \Owl\Application {
    protected $server;

    public function __construct($ip, $port) {
        parent::__construct();

        $this->server = $server = new \swoole_http_server($ip, $port);

        $server->on('Request', function($request, $response) {
            $request = new \Owl\Swoole\Request($request);
            $response = new \Owl\Swoole\Response($response);

            $this->execute($request, $response);
        });
    }

    /**
     * 获得swoole server实例
     *
     * @return \swoole_http_server
     */
    public function getSwooleServer() {
        return $this->server;
    }

    public function start() {
        $this->server->start();
    }
}
