<?php

namespace Tests;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Owl\Session::initialize();
    }

    protected function tearDown()
    {
        \Owl\Session::getInstance()->destroy();
        unset($_SESSION);
    }

    public function testInitialize()
    {
        $this->assertInstanceof('\Owl\Session', $_SESSION);
    }

    public function testIndirectModification()
    {
        $_SESSION['a']['b']['c'] = 1;
        $_SESSION['a']['b']['d'] = 2;

        $this->assertEquals($_SESSION['a']['b']['c'], 1);
        $this->assertEquals($_SESSION['a']['b']['d'], 2);
    }

    public function testSet()
    {
        $_SESSION['a']['b']['c'] = 1;
        $this->assertTrue(isset($_SESSION['a']['b']['c']));

        unset($_SESSION['a']['b']['c']);
        $this->assertFalse(isset($_SESSION['a']['b']['c']));
    }
}
