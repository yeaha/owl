<?php

namespace Tests\Http;

class MessageTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testImmutability()
    {
        $message = new Message();

        $this->assertSame('1.1', $message->getProtocolVersion());

        $message->setImmutability(false);
        $this->assertSame($message, $message->withProtocolVersion('1.0'));
        $this->assertSame('1.0', $message->getProtocolVersion());

        $message->setImmutability(true);
        $new = $message->withProtocolVersion('1.1');
        $this->assertSame('1.1', $new->getProtocolVersion());
        $this->assertFalse($new === $message);
    }

    public function testHeaders()
    {
        $message = new Message();

        $this->assertSame([], $message->getHeader('foo'));

        $message = $message->withHeader('foo', 'bar');
        $this->assertSame(['bar'], $message->getHeader('FOO'));
        $this->assertSame('bar', $message->getHeaderLine('foo'));

        $this->assertSame(['foo' => 'bar'], $message->getHeaders());

        $message = $message->withAddedHeader('foo', 'baz');
        $this->assertSame(['bar', 'baz'], $message->getHeader('FOO'));
        $this->assertSame(['foo' => ['bar', 'baz']], $message->getHeaders());
        $this->assertSame('bar,baz', $message->getHeaderLine('foo'));

        $message = $message->withHeader('foo', 'foobar');
        $this->assertSame(['foobar'], $message->getHeader('Foo'));

        $message = $message->withoutHeader('FOO');
        $this->assertSame([], $message->getHeader('foo'));
    }
}

class Message
{
    use \Owl\Http\MessageTrait;

    public function setImmutability($value)
    {
        $this->immutability = (bool) $value;
    }
}
