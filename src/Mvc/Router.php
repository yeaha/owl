<?php
namespace Owl\Mvc;

class Router {
    protected $base_path;

    /**
     * @example
     * [
     *     '/' => '\Controller',
     *     '/admin/' => '\Admin\Controller',
     * ]
     */
    protected $namespace = [];

    /**
     * @example
     * [
     *     '#^/user(\d+)?$#i' => '\Controller\User',
     *     '#^/admin(?:/)?#' => [
     *         '#^/admin/user/(\d)?#' => '\Admin\Controller\User',
     *     ],
     * ]
     */
    protected $rewrite = [];

    public function __construct(array $config = []) {
        if (isset($config['base_path'])) {
            $this->base_path = $this->normalizePath($config['base_path']);
        }

        if (isset($config['rewrite'])) {
            $this->rewrite = $config['rewrite'];
        }

        if (isset($config['namespace'])) {
            foreach ($config['namespace'] as $path => $namespace) {
                $path = strtolower($this->normalizePath($path));

                $namespace = implode('\\', array_map('ucfirst', explode('\\', $namespace)));
                $namespace = '\\'.trim($namespace, '\\');

                $this->namespace[$path] = $namespace;
            }

            // 保证"/"设置一定在最后
            krsort($this->namespace);
        }
    }

    /**
     * @param \Owl\Http\Request $request
     * @param \Owl\Http\Response $response
     * @return \Owl\Http\Response $response
     *
     * @throws \Owl\Http\Exception 404
     * @throws \Owl\Http\Exception 501
     * @throws \Exception Invalid controller class.
     */
    public function execute(\Owl\Http\Request $request, \Owl\Http\Response $response) {
        $path = $this->normalizePath($request->getRequestPath());
        list($class, $parameters) = $this->dispatch($path);

        if (!class_exists($class)) {
            throw \Owl\Http\Exception::factory(404);
        }

        $args = [$request, $response];
        if ($parameters) {
            $args = array_merge($args, $parameters);
        }

        $controller = new $class;

        // 如果__beforeExecute()返回了内容就直接返回内容
        if (method_exists($controller, '__beforeExecute') && ($data = call_user_func_array([$controller, '__beforeExecute'], $args))) {
            if (!($data instanceof \Owl\Http\Response)) {
                $response->setBody($data);
            }

            return $response;
        }

        $method = $request->getMethod();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        if (!in_array($method, array('HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'))) {
            throw \Owl\Http\Exception::factory(501);
        }

        if (!is_callable([$controller, $method])) {
            throw \Owl\Http\Exception::factory(405);
        }

        $data = call_user_func_array([$controller, $method], $args);
        if (!($data instanceof \Owl\Http\Response)) {
            $response->setBody($data);
        }

        if (method_exists($controller, '__afterExecute')) {
            $controller->__afterExecute($request, $response);
        }

        return $response;
    }

    /**
     * @param string $path
     * @return [string $class, array $parameters]
     * @throws \Owl\Http\Exception 404
     */
    protected function dispatch($path) {
        do {
            if ($base_path = $this->base_path) {
                if (stripos($this->normalizePath($path), $base_path) !== 0) {
                    break;
                }

                $path = '/'.substr($path, strlen($base_path));
            }

            if ($result = $this->byRewrite($path) ?: $this->byPath($path)) {
                return $result;
            }
        } while (false);

        throw \Owl\Http\Exception::factory(404);
    }

    /**
     * 正则表达式方式匹配controller
     *
     * @param string $path
     * @param array $rules
     * @return [string $class, array $parameters]
     */
    protected function byRewrite($path, array $rules = null) {
        if ($rules === null) {
            $rules = $this->rewrite;
        }

        foreach ($rules as $regexp => $class) {
            if (preg_match($regexp, $path, $match)) {
                if (is_array($class)) {
                    return $this->byRewrite($path, $class);
                }

                return [$class, array_slice($match, 1)];
            }
        }

        return false;
    }

    /**
     * 以路径匹配controller
     *
     * @param string $path
     * @return [string $class, array $parameters]
     */
    protected function byPath($path) {
        $pathinfo = pathinfo(strtolower($path));
        $path = ($pathinfo['dirname'] === '/')
              ? $pathinfo['dirname'] . $pathinfo['basename']
              : $pathinfo['dirname'] .'/'. $pathinfo['basename'];
        $path = $this->normalizePath($path);

        // 路径对应的controller namespace
        foreach ($this->namespace as $ns_path => $ns) {
            $ns_path = $this->normalizePath($ns_path);

            if (strpos($path, $ns_path) !== 0) {
                continue;
            }

            $class = [];
            $path = substr($path, strlen($ns_path)) ?: '/Index';
            foreach (explode('/', $path) as $word) {
                if ($word) {
                    $class[] = ucfirst($word);
                }
            }
            $class = $ns.'\\'.implode('\\', array_map('ucfirst', $class));

            return [$class, []];
        }

        return false;
    }

    protected function normalizePath($path) {
        if ($path === '/') { return '/'; }

        return rtrim($path, '/') .'/';
    }
}
