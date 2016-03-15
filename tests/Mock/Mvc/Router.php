<?php

namespace Tests\Mock\Mvc;

class Router extends \Owl\Mvc\Router
{
    public function testExecute($path, $method = 'GET')
    {
        $request = \Owl\Http\Request::factory([
            'uri' => $path,
            'method' => $method,
        ]);

        return $this->execute($request, new \Owl\Http\Response());
    }

    public function testDispatch($path)
    {
        $request = \Owl\Http\Request::factory([
            'uri' => $path,
            'method' => 'GET',
        ]);

        if ($router = $this->getDelegateRouter($request)) {
            return $router->dispatch($request);
        }

        return $this->dispatch($request);
    }

    public function dispatch($request)
    {
        $path = $this->getRequestPath($request);

        return $this->byRewrite($path) ?: $this->byPath($path);
    }
}
