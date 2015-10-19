<?php
namespace Tests\Http;

class RequestTest extends \PHPUnit_Framework_TestCase {
    public function testGet() {
        $request = \Owl\Http\Request::factory([
            'uri' => '/foobar?a=b',
            'get' => [
                'foo' => '1',
                'bar' => '',
            ],
        ]);

        $this->assertEquals('b', $request->get('a'));
        $this->assertEquals('1', $request->get('foo'));
        $this->assertSame('', $request->get('bar'));
        $this->assertTrue($request->hasGet('bar'));
        $this->assertFalse($request->hasGet('baz'));
        $this->assertSame(['a' => 'b', 'foo' => '1', 'bar' => ''], $request->get());
    }

    public function testPost() {
        $request = \Owl\Http\Request::factory([
            'method' => 'post',
            'post' => [
                'foo' => '1',
                'bar' => '',
            ],
        ]);

        $this->assertEquals('1', $request->post('foo'));
        $this->assertSame('', $request->post('bar'));
        $this->assertTrue($request->hasPost('bar'));
        $this->assertFalse($request->hasPost('baz'));
        $this->assertSame(['foo' => '1', 'bar' => ''], $request->post());
    }

    public function testCookie() {
        $request = \Owl\Http\Request::factory([
            'cookies' => [
                'foo' => '1',
                'bar' => '',
            ],
        ]);

        $this->assertEquals('1', $request->getCookieParam('foo'));
        $this->assertSame('', $request->getCookieParam('bar'));
        $this->assertSame(['foo' => '1', 'bar' => ''], $request->getCookies());
    }

    public function testHeaders() {
        $request = \Owl\Http\Request::factory([
            'headers' => [
                'Accept-Encoding' => 'gzip,deflate',
                'Accept-Language' => 'en-us,en;q=0.8,zh-cn;q=0.5,zh;q=0.3',
                'Connection' => 'keepalive',
            ],
        ]);

        $this->assertEquals(['gzip', 'deflate'], $request->getHeader('accept-encoding'));
        $this->assertEquals($request->getHeader('accept-encoding'), $request->getHeader('ACCEPT-ENCODING'));
        $this->assertSame([
            'accept-encoding' => ['gzip', 'deflate'],
            'accept-language' => ['en-us', 'en;q=0.8', 'zh-cn;q=0.5', 'zh;q=0.3'],
            'connection' => ['keepalive'],
        ], $request->getHeaders());
    }

    public function testMethod() {
        foreach (['get', 'post', 'put', 'delete'] as $method) {
            $request = \Owl\Http\Request::factory([
                'method' => $method,
            ]);

            $this->assertEquals(strtoupper($method), $request->getMethod());
            $this->assertTrue(call_user_func([$request, 'is'.$method]));
        }

        $request = \Owl\Http\Request::factory([
            'method' => 'POST',
            'post' => [
                '_method' => 'PUT',
            ],
        ]);
        $this->assertEquals('PUT', $request->getMethod());

        $request = \Owl\Http\Request::factory([
            'method' => 'POST',
            'headers' => [
                'x-http-method-override' => 'DELETE',
            ],
            'post' => [
                'foo' => 'bar',
            ],
        ]);
        $this->assertEquals('DELETE', $request->getMethod());
    }

    public function testRequestURI() {
        $uri = '/foobar.json?foo=bar';
        $request = \Owl\Http\Request::factory([
            'uri' => $uri,
        ]);

        $this->assertEquals($uri, $request->getRequestTarget());
        $this->assertEquals('/foobar.json', $request->getUri()->getPath());
        $this->assertEquals('json', $request->getUri()->getExtension());
    }
}
