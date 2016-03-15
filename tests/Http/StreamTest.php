<?php

namespace Tests\Http;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    public function testResource()
    {
        $stream = new \Owl\Http\ResourceStream(fopen('php://memory', 'r+'));

        $this->assertSame(0, $stream->getSize());

        $stream->write('foobar');
        $this->assertSame(6, $stream->getSize());
        $this->assertSame('foobar', (string) $stream);

        $stream->rewind();
        $this->assertSame('f', $stream->read(1));
        $this->assertSame(1, $stream->tell());
        $this->assertSame('oo', $stream->read(2));
        $this->assertSame('bar', $stream->read(10));

        $stream->seek(2);
        $this->assertSame('ob', $stream->read(2));
        $this->assertFalse($stream->eof());
    }

    public function testString()
    {
        $stream = new \Owl\Http\StringStream('foo');

        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', (string) $stream);

        $stream->write('bar');
        $this->assertSame(6, $stream->getSize());
        $this->assertSame('foobar', (string) $stream);

        $stream->rewind();
        $this->assertSame('f', $stream->read(1));
        $this->assertSame(1, $stream->tell());
        $this->assertSame('oo', $stream->read(2));
        $this->assertSame('bar', $stream->read(10));
        $this->assertTrue($stream->eof());

        $stream->seek(2);
        $this->assertSame('ob', $stream->read(2));
        $this->assertFalse($stream->eof());
    }

    public function testIteartor()
    {
        $fn = function () {
            $words = ['foo', 'bar', 'baz'];

            foreach ($words as $word) {
                yield $word;
            }
        };

        $stream = new \Owl\Http\IteratorStream($fn());
        $iterator = $stream->iterator();

        foreach ($stream->iterator() as $string) {
            switch ($stream->tell()) {
                case 1:
                    $this->assertSame('foo', $string);
                    break;
                case 2:
                    $this->assertSame('bar', $string);
                    break;
                case 3:
                    $this->assertSame('baz', $string);
                    break;
            }
        }

        $this->assertTrue($stream->eof());

        $stream = new \Owl\Http\IteratorStream($fn());
        $this->assertSame('foobarbaz', (string) $stream);
    }
}
