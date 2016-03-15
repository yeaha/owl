<?php

namespace tests\Mvc;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchByPath()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $this->assertSame(['\Controller\Index', []], $router->testDispatch('/'));

        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar/'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/FOO/BAR'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar.json'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar.html'));
    }

    public function testDisaptchByRewrite()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
            'rewrite' => [
                '#^/user/(\d+)$#' => '\Controller\User',
                '#^/link/([0-9a-zA-Z]+)#' => '\Controller\Link',
            ],
        ]);

        $this->assertSame(['\Controller\User', []], $router->testDispatch('/user'));
        $this->assertSame(['\Controller\User', ['1']], $router->testDispatch('/user/1'));
        $this->assertSame(['\Controller\Link', ['4A5g76z']], $router->testDispatch('/link/4A5g76z'));
    }

    public function testDispatchBasePath()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'base_path' => '/foobar',
            'namespace' => '\Controller',
            'rewrite' => [
                '#^/baz#' => '\Controller\Baz',
            ],
        ]);

        $this->assertSame(['\Controller\Index', []], $router->testDispatch('/foobar'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foobar/foo/bar'));
        $this->assertSame(['\Controller\Baz', []], $router->testDispatch('/foobar/baz'));

        $this->setExpectedException('\Owl\Http\Exception', '', 404);
        $router->testDispatch('/baz');
    }

    public function testDelegate()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'base_path' => '/foo/bar',
            'namespace' => '\Controller',
        ]);

        $admin_router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Admin\Controller',
            'rewrite' => [
                '#^/baz#' => '\Admin\Controller\Baz',
            ],
        ]);

        $router->delegate('/admin', $admin_router);

        $this->assertSame(['\Controller\Baz', []], $router->testDispatch('/foo/bar/baz'));
        $this->assertSame(['\Admin\Controller\Index', []], $router->testDispatch('/foo/bar/admin'));
        $this->assertSame(['\Admin\Controller\Baz', []], $router->testDispatch('/foo/bar/admin/baz'));

        $admin_router->middleware(function ($request, $response) {
            $response->write('admin router');
            yield false;
        });

        $response = $router->testExecute('/foo/bar/admin');
        $this->assertEquals('admin router', $response->getBody());
    }

    public function testMiddleware()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $test = [];

        $router->middleware('/foo/bar', function ($request, $response) use (&$test) {
            $test[] = 1;

            yield true;
        });

        $router->middleware('/foo', function ($request, $response) use (&$test) {
            $test[] = 2;

            yield false;
        });

        $response = $router->testExecute('/foo/bar/baz');
        $this->assertSame([1, 2], $test);
    }

    public function testExceptionHandler()
    {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $router->setExceptionHandler(function ($exception, $request, $response) {
            $response->write('page not found');
        });

        $admin_router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Admin\Controller',
        ]);
        $router->delegate('/admin', $admin_router);

        $admin_router->setExceptionHandler(function ($exception, $request, $response) {
            $response->write('admin page not found');
        });

        $response = $router->testExecute('/foobar/baz');
        $this->assertEquals('page not found', $response->getBody());

        $response = $router->testExecute('/admin/baz');
        $this->assertEquals('admin page not found', $response->getBody());
    }
}
