<?php

namespace Tests\Context;

class RedisTest extends \PHPUnit_Framework_TestCase
{
    public function testRedisContext()
    {
        $config = [
            'token' => uniqid(),
            'ttl' => 300,
            'service' => new \Owl\Service\Redis(),
        ];
        $handler = new \Tests\Mock\Context\Redis($config);

        $this->assertSame(array(), $handler->get());
        $this->assertFalse($handler->isDirty());

        $handler->set('foo', 1);
        $this->assertTrue($handler->isDirty());

        $handler->set('bar', 2);
        $this->assertTrue($handler->save());
        $this->assertFalse($handler->isDirty());

        $ttl = $handler->getTimeout();
        $this->assertTrue($ttl && $ttl > 0);

        $handler = new \Tests\Mock\Context\Redis($config);
        $this->assertEquals(1, $handler->get('foo'));
        $this->assertEquals(2, $handler->get('bar'));

        $handler->remove('bar');
        $this->assertTrue($handler->isDirty());
        $this->assertTrue($handler->save());

        $handler = new \Tests\Mock\Context\Redis($config);
        $this->assertTrue($handler->has('foo'));
        $this->assertFalse($handler->has('bar'));

        $handler = new \Tests\Mock\Context\Redis($config);
        $handler->set('foo', '1');
        $this->assertFalse($handler->isDirty());
        $handler->set('foo', 2);
        $this->assertTrue($handler->isDirty());

        $handler = new \Tests\Mock\Context\Redis($config);
        $handler->remove('foobar');
        $this->assertFalse($handler->isDirty());
        $handler->remove('foo');
        $this->assertTrue($handler->isDirty());

        $handler->clear();
        $handler->save();
    }
}
