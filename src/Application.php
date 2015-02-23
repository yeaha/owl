<?php
namespace Owl;

/**
 * @example
 *
 * $app = new \Owl\Application('127.0.0.1', 12345);
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
 * $router = new \Owl\Mvc\Router;
 * $app->middleware(function($request, $response) use ($router) {
 *     $router->dispatch($request, $response);
 *
 *     yield true;
 * });
 *
 * $app->setExceptionHandler(function($exception, $request, $response) {
 *     $response->setStatus(500);
 *     $response->setBody($exception->getMessage());
 * });
 *
 * $app->start();
 */

if (!extension_loaded('swoole')) {
    throw new \Exception('Require php extension "swoole"');
}

class Application {
    protected $exception_handler;
    protected $middleware;
    protected $server;

    public function __construct($ip, $port) {
        $this->server = $server = new \swoole_http_server($ip, $port);

        $server->on('Request', function($request, $response) {
            $request = new \Owl\Http\Request($request);
            $response = new \Owl\Http\Response($response);

            $this->execute($request, $response);
        });

        $this->middleware = new \Owl\Middleware;
    }

    /**
     * 魔法方法，把方法调用代理到swoole server实例上
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args) {
        return call_user_func_array(array($this->server, $method), $args);
    }

    /**
     * 获得swoole server实例
     *
     * @return \swoole_http_server
     */
    public function getSwooleServer() {
        return $this->server;
    }

    /**
     * 添加中间件
     *
     * @param \Closure $handler
     * @return $this
     */
    public function middleware(\Closure $handler) {
        $this->middleware->insert($handler);
        return $this;
    }

    public function resetMiddleware() {
        $this->middleware->reset();
    }

    /**
     * 添加异常处理逻辑
     *
     * @param \Closure $handler
     * @return $this
     */
    public function setExceptionHandler(\Closure $handler) {
        $this->exception_handler = $handler;
        return $this;
    }

    /**
     * 响应请求，依次执行添加的中间件逻辑
     *
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @return void
     */
    public function execute(\Owl\Http\Request $request, \Owl\Http\Response $response) {
        try {
            $this->middleware->execute($request, $response);
        } catch (\Exception $exception) {
            $handler = $this->exception_handler ?: function($exception, $request, $response) {
                $response->setStatus(500);
                $response->setBody('');
            };

            $handler($exception, $request, $response);
        }

        $response->end();
    }

    /**
     * class loader
     *
     * @param string $namespace
     * @param string $path
     * @param string $classname For test
     * @return void|string
     */
    static public function registerNamespace($namespace, $path, $classname = null) {
        $namespace = trim($namespace, '\\');
        $path = rtrim($path, '/\\');

        $loader = function($classname, $return_filename = false) use ($namespace, $path) {
            if (class_exists($classname, false) || interface_exists($classname, false)) {
                return true;
            }

            $classname = trim($classname, '\\');

            if ($namespace && stripos($classname, $namespace) !== 0) {
                return false;
            } else {
                $filename = trim(substr($classname, strlen($namespace)), '\\');
            }

            $filename = $path .DIRECTORY_SEPARATOR. str_replace('\\', DIRECTORY_SEPARATOR, $filename).'.php';

            if ($return_filename) {
                return $filename;
            } else {
                if (!file_exists($filename)) {
                    return false;
                }

                require $filename;
                return class_exists($classname, false) || interface_exists($classname, false);
            }
        };

        if ($classname === null) {
            spl_autoload_register($loader);
        } else {
            return $loader($classname, true);
        }
    }
}
