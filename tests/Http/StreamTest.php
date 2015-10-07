<?php
namespace Tests\Http;

class StreamTest extends \PHPUnit_Framework_TestCase {
    public function test() {
        $stream = new \Owl\Http\Stream(fopen('php://memory', 'r+'));

        $this->assertSame(0, $stream->getSize());

        $stream->write('foobar');
        $this->assertSame(6, $stream->getSize());
        $this->assertSame('foobar', (string)$stream);

        $stream->rewind();
        $this->assertSame('f', $stream->read(1));
        $this->assertSame(1, $stream->tell());
        $this->assertSame('oo', $stream->read(2));
        $this->assertSame('bar', $stream->read(10));

        $stream->seek(2);
        $this->assertSame('ob', $stream->read(2));
        $this->assertFalse($stream->eof());
    }
}
