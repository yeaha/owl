<?php
namespace Owl\Mvc;

/**
 * @example
 *
 * $router = new Router([
 *     'base_path' => '/foobar',        // optional
 *     'namespace' => '\Controller',
 *     'rewrite' => [                   // optional
 *         '#^/user(\d+)?$#i' => '\Controller\User',
 *     ],
 * ]);
 *
 * $admin_router = new Rounter([
 *     'namespace' => '\Admin\Controller',
 *     'rewrite' => [
 *         '#^/user/(\d)?#' => '\Admin\Controller\User',
 *     ],
 * ]);
 * $router->delegate('/admin', $admin_router);
 */
class Router {
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $children = [];

    public function __construct(array $config = []) {
        (new \Owl\Parameter\Checker)->execute($config, [
            'namespace' => ['type' => 'string'],
            'base_path' => ['type' => 'string', 'required' => false, 'regexp' => '#^/.+#'],
            'rewrite' => ['type' => 'hash', 'required' => false, 'allow_empty' => true],
        ]);

        if (substr($config['namespace'], -1, 1) !== '\\') {
            $config['namespace'] = $config['namespace'].'\\';
        }

        if (isset($config['base_path'])) {
            $config['base_path'] = $this->normalizePath($config['base_path']);
        }

        $this->config = $config;
    }

    /**
     * @param string $key
     * @return mixed|false
     */
    public function getConfig($key) {
        return isset($this->config[$key])
             ? $this->config[$key]
             : false;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setConfig($key, $value) {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * 把指定路径的访问委托到另外一个router
     *
     * @param string $path
     * @param Owl\Mvc\Router $router
     * @return $this
     */
    public function delegate($path, Router $router) {
        $path = $this->normalizePath($path);

        if ($base_path = $this->getConfig('base_path')) {
            $path = $base_path .ltrim($path, '/');
        }

        $router->setConfig('base_path', $path);

        $this->children[$path] = $router;

        return $this;
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
        if (!$result = $this->dispatch($request->getRequestPath())) {
            throw \Owl\Http\Exception::factory(404);
        }

        list($class, $parameters) = $result;

        if (!class_exists($class)) {
            throw \Owl\Http\Exception::factory(404);
        }

        $controller = new $class;
        $controller->request = $request;
        $controller->response = $response;

        // 如果__beforeExecute()返回了内容就直接返回内容
        if (method_exists($controller, '__beforeExecute') && ($data = call_user_func_array([$controller, '__beforeExecute'], $parameters))) {
            if (!($data instanceof \Owl\Http\Response)) {
                $response->setBody($data);
            }

            return $response;
        }

        $method = $request->getMethod();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        if (!in_array($method, ['HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
            throw \Owl\Http\Exception::factory(501);
        }

        if (!is_callable([$controller, $method])) {
            throw \Owl\Http\Exception::factory(405);
        }

        $data = call_user_func_array([$controller, $method], $parameters);
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
     */
    public function dispatch($path) {
        $path = $this->normalizePath($path);
        foreach ($this->children as $delegate_path => $router) {
            if (strpos($path, $delegate_path) === 0) {
                return $router->dispatch($path);
            }
        }

        $dispatch_path = $this->trimBasePath($path);

        if ($result = $this->byRewrite($dispatch_path) ?: $this->byPath($dispatch_path)) {
            return $result;
        }

        return false;
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
            $rules = $this->getConfig('rewrite') ?: [];
        }

        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
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

        if ($pathinfo['dirname'] === '\\') {
            $pathinfo['dirname'] = '/';
        }

        $path = ($pathinfo['dirname'] === '/')
              ? $pathinfo['dirname'] . $pathinfo['basename']
              : $pathinfo['dirname'] .'/'. $pathinfo['basename'];
        $path = $this->normalizePath($path);

        if ($path === '/') {
            $path = '/index';
        }

        $class = [];
        foreach (explode('/', $path) as $word) {
            if ($word) {
                $class[] = $word;
            }
        }
        $class = $this->getConfig('namespace').implode('\\', array_map('ucfirst', $class));
        return [$class, []];
    }

    protected function normalizePath($path) {
        if ($path === '/') {
            return '/';
        }

        if (substr($path, -1, 1) !== '/') {
            $path .= '/';
        }

        return $path;
    }

    /**
     * 去掉路径内的base_path
     *
     * @param string $path
     * @return string
     */
    protected function trimBasePath($path) {
        $base_path = $this->getConfig('base_path');

        if (!$base_path || $base_path === '/') {
            return $path;
        }

        if (stripos($path, $base_path) !== 0) {
            throw \Owl\Http\Exception::factory(404);
        }

        return '/'.substr($path, strlen($base_path));
    }
}
