<?php
namespace Owl;

/**
 * @example
 *
 * $app = new \Owl\Application('127.0.0.1', 12345);
 *
 * $app->middleware(function($request, $response, $next) {
 *     $start = microtime(true);
 *
 *     yield $next;
 *
 *     $use_time = (microtime(true) - $start) * 1000;
 *     $response->setHeader('use-time', (int)$use_time.'ms');
 * });
 *
 * $app->middelware(function($request, $response, $next) {
 *     yield $next;
 *
 *     $logger = new \Monolog\Logger;
 *     $logger->debug(sprintf('Request %s, status: %d'), $request->getRequestURI(), $response->getStatus());
 * });
 *
 * $router = new \Owl\Mvc\Router;
 * $app->middleware(function($request, $response, $next) use ($router) {
 *     $router->dispatch($request, $response);
 *
 *     yield $next;
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
    protected $callback;
    protected $exception_handler;
    protected $middleware = [];
    protected $server;

    public function __construct($ip, $port) {
        $this->server = $server = new \swoole_http_server($ip, $port);

        $server->on('Request', function($request, $response) {
            $request = new \Owl\Http\Request($request);
            $response = new \Owl\Http\Response($response);

            $this->callback($request, $response);
        });
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
        $this->middleware[] = $handler;
        $this->callback = null;
        return $this;
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
    public function callback(\Owl\Http\Request $request, \Owl\Http\Response $response) {
        $callback = $this->buildCallback();

        try {
            $stack = [];

            while ($callback) {
                $generator = $callback($request, $response);

                if (!$generator || !($generator instanceof \Generator)) {
                    throw new \Exception('Missing "yield $next;" in middleware handler');
                }

                $callback = $generator->current();
                $stack[] = $generator;
            }

            while ($generator = array_pop($stack)) {
                $generator->next();
            }
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
     * 把添加的中间件打包为单个回调函数
     *
     * @return \Closure
     */
    protected function buildCallback() {
        if (!$this->middleware) {
            return false;
        }

        if ($this->callback) {
            return $this->callback;
        }

        $next = null;
        foreach (array_reverse($this->middleware) as $handler) {
            $next = function($request, $response) use ($handler, $next) {
                return call_user_func($handler, $request, $response, $next);
            };
        }

        return $this->callback = $next;
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
