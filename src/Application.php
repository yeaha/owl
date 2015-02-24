<?php
namespace Owl;

/**
 * @example
 *
 * $app = new \Owl\Application();
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

class Application {
    protected $exception_handler;
    protected $middleware;

    public function __construct() {
        $this->middleware = new \Owl\Middleware;
    }

    /**
     * 添加中间件
     *
     * @param callable $handler
     * @return $this
     */
    public function middleware($handler) {
        $this->middleware->insert($handler);
        return $this;
    }

    /**
     * 清除所有已添加的中间件
     *
     * @return void
     */
    public function resetMiddleware() {
        $this->middleware->reset();
    }

    /**
     * 添加异常处理逻辑
     *
     * @param \Closure $handler
     * @return $this
     */
    public function setExceptionHandler($handler) {
        $this->exception_handler = $handler;
        return $this;
    }

    public function start() {
        $request = new \Owl\Http\Request;
        $response = new \Owl\Http\Response;

        return $this->execute($request, $response);
    }

    /**
     * 响应请求，依次执行添加的中间件逻辑
     *
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @return void
     */
    protected function execute(\Owl\Http\Request $request, \Owl\Http\Response $response) {
        try {
            $this->middleware->execute($request, $response);
        } catch (\Exception $exception) {
            $handler = $this->exception_handler ?: function($exception, $request, $response) {
                $response->setStatus(500);
                $response->setBody('');
            };

            call_user_func($handler, $exception, $request, $response);
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
