<?php
namespace Tests\Http;

use Owl\Http\StringStream;

class StringStreamTest extends \PHPUnit_Framework_TestCase {
    public function test() {
        $stream = new StringStream('foo');

        $this->assertSame(3, $stream->getSize());
        $this->assertSame('foo', (string)$stream);
        $this->assertSame('foo', $stream->getContents());

        $stream->write('bar');
        $this->assertSame(6, $stream->getSize());
        $this->assertSame('foobar', (string)$stream);
        $this->assertSame('foobar', $stream->getContents());

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
}
