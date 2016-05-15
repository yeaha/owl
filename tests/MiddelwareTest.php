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
            $result = yield false;

            return 'bar';
        })->bindTo($this));

        $middleware->insert(function () {
            yield;

            return 'foo';
        });

        $result = $middleware->execute();
        $this->assertEquals('bar', $result);
    }

    public function testInvalidHandler()
    {
        $middleware = new \Owl\Middleware();

        $this->setExpectedExceptionRegExp('\Exception', '/is not callable/');
        $middleware->insert(1);
    }
}
