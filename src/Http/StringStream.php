<?php

namespace Owl\Http;

class StringStream extends \Owl\Http\Stream
{
    protected $position = 0;
    protected $seekable = true;
    protected $readable = true;
    protected $writable = true;

    public function __construct($string = '')
    {
        $this->stream = $string;
        $this->position = strlen($string);
    }

    public function __toString()
    {
        return $this->stream;
    }

    public function getSize()
    {
        return strlen($this->stream);
    }

    public function tell()
    {
        return $this->position;
    }

    public function eof()
    {
        return $this->position === strlen($this->stream);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->position = min($offset, $this->getSize());
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function write($string)
    {
        $this->stream .= $string;
        $this->position = $this->getSize();

        return strlen($string);
    }

    public function read($length)
    {
        $result = substr($this->stream, $this->position, $length);

        $this->position = min($this->position + $length, $this->getSize());

        return ($result === false) ? '' : $result;
    }

    public function getContents()
    {
        return substr($this->stream, $this->position);
    }
}
