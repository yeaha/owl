<?php

namespace Tests;

class Base
{
}
class Foo
{
}
class User extends Base
{
}

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Owl\Container
     */
    protected $container;

    public function testGet()
    {
        $this->container->set('user1', function () {
             return new User();
          });

        $this->assertInstanceOf('\Tests\User',  $this->container->get('user1'));
        $this->assertInstanceOf('\Tests\Base', $this->container->get('user1'));
    }

    public function testGetCallback()
    {
        $this->container->set('user1', function () {
            return new User();
        });
        $this->assertInstanceOf('Closure', $this->container->getCallback('user1'));
    }

    public function testGetUndefinedMember()
    {
        $this->setExpectedExceptionRegExp('\Exception', '/does not exists/');

        $this->container->get('undefined key');
    }

    public function testHas()
    {
        $this->container->set('obj1', function () {
            return stdClass();
        });
        $this->assertTrue($this->container->has('obj1'));
        $this->assertFalse($this->container->has('obj2'));
    }

    public function testRemove()
    {
        $this->container->set('a', function () {
             return stdClass();
         });
        $this->assertTrue($this->container->remove('a'));
    }

    protected function setUp()
    {
        $this->container = new \Owl\Container();
    }
}
