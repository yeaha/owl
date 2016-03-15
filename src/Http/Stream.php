<?php

namespace Owl\Http;

abstract class Stream implements \Psr\Http\Message\StreamInterface
{
    protected $stream;
    protected $seekable = false;
    protected $readable = false;
    protected $writable = false;

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        $this->detach();
    }

    public function detach()
    {
        if (!$this->stream) {
            return;
        }

        $stream = $this->stream;

        $this->stream = null;
        $this->seekable = $this->readable = $this->writable = false;

        return $stream;
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function getSize()
    {
        return;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->seekable) {
            throw new \Exception('Stream is not seekable');
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function write($string)
    {
        if (!$this->writable) {
            throw new \Exception('Stream is not writable');
        }
    }

    public function read($length)
    {
        if (!$this->readable) {
            throw new \Exception('Stream is not readable');
        }
    }

    public function getMetaData($key = null)
    {
        return $key === null ? [] : null;
    }
}
