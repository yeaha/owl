<?php
namespace Tests\Mvc;

class RouterTest extends \PHPUnit_Framework_TestCase {
    public function testDispatchByPath() {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $this->assertSame(['\Controller\Index', []], $router->testDispatch('/'));

        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar/'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/FOO/BAR'));
    }

    public function testDisaptchByRewrite() {
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

    public function testDispatchBasePath() {
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

        $this->setExpectedException('\Owl\Http\Exception', null, 404);
        $router->testDispatch('/baz');
    }

    public function testDelegate() {
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
    }

    public function testMiddleware() {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $router->middleware('/foobar', function($request, $response) {
            $response->setBody('foobar');

            yield false;
        });

        $request = \Owl\Http\Request::factory([
            'uri' => '/foobar/baz',
            'method' => 'GET',
        ]);
        $response = new \Owl\Http\Response;

        $router->execute($request, $response);
        $this->assertEquals('foobar', $response->getBody());
    }

    public function testExceptionHandler() {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => '\Controller',
        ]);

        $router->setExceptionHandler(function($exception, $request, $response) {
            $response->setBody('page not found');
        });

        $request = \Owl\Http\Request::factory([
            'uri' => '/foobar/baz',
            'method' => 'GET',
        ]);
        $response = new \Owl\Http\Response;

        $router->execute($request, $response);
        $this->assertEquals('page not found', $response->getBody());
    }
}
