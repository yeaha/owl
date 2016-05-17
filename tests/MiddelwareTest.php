<?php
namespace Tests;

class MiddelwareTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $middleware = new \Owl\Middleware();

        $data = [];

        $middleware->insert(function () use (&$data) {
            $data[] = 1;
            yield true;
            $data[] = 2;
        });

        $middleware->insert(function () use (&$data) {
            $data[] = 3;
            yield false;
            $data[] = 4;
        });

        $middleware->insert(function () use (&$data) {
            $data[] = 5;
            yield true;
            $data[] = 6;
        });

        $middleware->execute();
        $this->assertSame([1, 3, 4, 2], $data);

        // test execute empty queue
        $middleware->reset();
        $middleware->execute();
    }

    public function testReturnValue()
    {
        $middleware = new \Owl\Middleware();

        $middleware->insert((function () {
            $result = yield;

            $this->assertEquals('foobar', $result);
        })->bindTo($this));

        $middleware->insert(function () {
            yield;

            return 'foobar';
        });

        $result = $middleware->execute();
        $this->assertEquals('foobar', $result);

        ///////////////////////////////////////////
        $middleware = new \Owl\Middleware();

        $middleware->insert((function () {
            $result = yield;

            $this->assertEquals('bar', $result);
        })->bindTo($this));

        // stop here
        $middleware->insert(function () {
            yield false;

            return 'bar';
        });

        $middleware->insert(function () {
            return 'foo';
        });

        $result = $middleware->execute();
        $this->assertEquals('bar', $result);

        ///////////////////////////////////////////
        $middleware = new \Owl\Middleware();

        $middleware->insert(function () {
            yield;

            return 'bar';
        });

        // return without yield
        $middleware->insert(function () {
            return 'foo';
        });

        $result = $middleware->execute();
        $this->assertEquals('foo', $result);

        ///////////////////////////////////////////
        $middleware = new \Owl\Middleware();

        // ignore this return value
        $middleware->insert(function () {
            return 'bar';
        });

        $middleware->insert(function () {
            yield;
        });

        $result = $middleware->execute();
        $this->assertNull($result);
    }

    public function testArguments()
    {
        $middleware = new \Owl\Middleware();

        $middleware->insert((function ($a) {
            $this->assertEquals('a', $a);

            yield new \Owl\Middleware\Arguments('b', 'c');
        })->bindTo($this));

        $middleware->insert(function () {});
        $middleware->insert((function ($b, $c) {
            $this->assertEquals('b', $b);
            $this->assertEquals('c', $c);
        })->bindTo($this));

        $middleware->execute(['a']);
    }

    public function testInvalidHandler()
    {
        $middleware = new \Owl\Middleware();

        $this->setExpectedExceptionRegExp('\Exception', '/is not callable/');
        $middleware->insert(1);
    }
}
