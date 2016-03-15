<?php

namespace Tests\Traits;

class DecoratorTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $bar = new Bar();

        $bar->setMessage('foobar');

        $this->assertEquals('foobar', $bar->getMessage());
        $this->assertEquals('foobar', $bar->message);
    }
}

class Foo
{
    public $message;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}

class Bar
{
    use \Owl\Traits\Decorator;

    public function __construct()
    {
        $this->reference = new Foo();
    }
}
