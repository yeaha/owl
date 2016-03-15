<?php

namespace Owl\Swoole;

/*
 * @example
 *
 * $app = new \Owl\Swoole\Application('127.0.0.1', 12345);
 *
 * $app->middleware(function($request, $response) {
 *     $start = microtime(true);
 *
 *     yield true;
 *
 *     $use_time = (microtime(true) - $start) * 1000;
 *     $response->setHeader('use-time', (int)$use_time.'ms');
 * });
 *
 * $app->middelware(function($request, $response) {
 *     yield true;
 *
 *     $logger = new \Monolog\Logger;
 *     $logger->debug(sprintf('Request %s, status: %d'), $request->getRequestURI(), $response->getStatus());
 * });
 *
 * $router = new \Owl\Mvc\Router([
 *     'namespace' => '\Controller',
 * ]);
 * $app->middleware(function($request, $response) use ($router) {
 *     $router->execute($request, $response);
 *
 *     yield true;
 * });
 *
 * $app->setExceptionHandler(function($exception, $request, $response) {
 *     $response->withStatus(500);
 *     $response->setBody($exception->getMessage());
 * });
 *
 * $app->start();
 */

if (!extension_loaded('swoole')) {
    throw new \Exception('Require php extension "swoole"');
}

class Application extends \Owl\Application
{
    protected $server;

    public function __construct($ip, $port)
    {
        parent::__construct();

        $this->server = $server = new \swoole_http_server($ip, $port);

        $server->on('Request', function ($request, $response) {
            $request = new \Owl\Swoole\Request($request);
            $response = new \Owl\Swoole\Response($response);

            $this->execute($request, $response);
        });
    }

    /**
     * 获得swoole server实例.
     *
     * @return \swoole_http_server
     */
    public function getSwooleServer()
    {
        return $this->server;
    }

    public function start()
    {
        $this->server->start();
    }
}
