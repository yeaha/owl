<?php
namespace Tests\Mvc;

class RouterTest extends \PHPUnit_Framework_TestCase {
    public function testDispatchByPath() {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => [
                '/' => '\Controller',
                '/admin' => '\Admin\Controller',
            ],
        ]);

        $this->assertSame(['\Controller\Index', []], $router->testDispatch('/'));

        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foo/bar/'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/FOO/BAR'));

        $this->assertSame(['\Admin\Controller\Index', []], $router->testDispatch('/admin/'));
        $this->assertSame(['\Admin\Controller\Index', []], $router->testDispatch('/admin'));

        $this->assertSame(['\Admin\Controller\Foo\Bar', []], $router->testDispatch('/admin/foo/bar'));
        $this->assertSame(['\Admin\Controller\Foo\Bar', []], $router->testDispatch('/admin/foo/bar/'));
    }

    public function testDisaptchByRewrite() {
        $router = new \Tests\Mock\Mvc\Router([
            'namespace' => [
                '/' => '\Controller',
                '/admin' => '\Admin\Controller',
            ],
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
            'namespace' => [
                '/' => '\Controller',
            ],
        ]);

        $this->assertSame(['\Controller\Index', []], $router->testDispatch('/foobar'));
        $this->assertSame(['\Controller\Foo\Bar', []], $router->testDispatch('/foobar/foo/bar'));

        $this->setExpectedException('\Owl\Http\Exception', null, 404);
        $router->testDispatch('/baz');
    }
}
