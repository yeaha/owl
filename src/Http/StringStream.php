<?php
namespace Owl\Http;

class StringStream implements \Psr\Http\Message\StreamInterface {
    protected $contents = '';
    protected $position = 0;
    protected $closed = false;
    protected $seekable = true;
    protected $readable = true;
    protected $writable = true;

    public function __construct($contents = '') {
        $this->contents = $contents;
        $this->position = strlen($contents);
    }

    public function __destruct() {
        $this->close();
    }

    public function __toString() {
        return $this->getContents();
    }

    public function isSeekable() {
        return $this->seekable;
    }

    public function isReadable() {
        return $this->readable;
    }

    public function isWritable() {
        return $this->writable;
    }

    public function close() {
        $this->closed = true;
        $this->detach();
    }

    public function detach() {
        $this->closed = true;

        $result = $this->contents;
        $this->contents = '';

        return $result;
    }

    public function getSize() {
        return strlen($this->contents);
    }

    public function tell() {
        return $this->position;
    }

    public function eof() {
        return $this->position === strlen($this->contents);
    }

    public function seek($offset, $whence = SEEK_SET) {
        $this->position = min($offset, $this->getSize());
    }

    public function rewind() {
        $this->position = 0;
    }

    public function write($string) {
        $this->contents .= $string;
        $this->position = $this->getSize();

        return strlen($string);
    }

    public function read($length) {
        $result = substr($this->contents, $this->position, $length);

        $this->position = min($this->position+$length, $this->getSize());

        return ($result === false) ? '' : $result;
    }

    public function getContents() {
        return $this->contents;
    }

    public function getMetadata($key = null) {
    }
}
