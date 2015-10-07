<?php
namespace Owl\Http;

class Stream implements \Psr\Http\Message\StreamInterface {
    static private $read_write_mode = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];

    protected $stream;
    protected $seekable;
    protected $readable;
    protected $writable;

    public function __construct($stream, $options = []) {
        if (!is_resource($stream)) {
            throw new \Exception('Stream must be a resource');
        }

        $this->stream = $stream;

        $meta = stream_get_meta_data($stream);
        $this->seekable = $meta['seekable'];
        $this->readable = isset(self::$read_write_mode['read'][$meta['mode']]);
        $this->writable = isset(self::$read_write_mode['write'][$meta['mode']]);
    }

    public function __destruct() {
        $this->close();
    }

    public function __toString() {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $ex) {
            return '';
        }
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
        if ($this->stream && is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }

    public function detach() {
        if (!$this->stream) {
            return null;
        }

        $stream = $this->stream;

        $this->stream = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $stream;
    }

    public function getSize() {
        if (!$this->stream) {
            return null;
        }

        $stat = fstat($this->stream);
        return isset($stat['size']) ? (int)$stat['size'] : null;
    }

    public function tell() {
        $position = ftell($this->stream);

        if ($position === false) {
            throw new \Exception('Unable to get position of stream');
        }

        return $position;
    }

    public function eof() {
        return !$this->stream || feof($this->stream);
    }

    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->seekable) {
            throw new \Exception('Stream is not seekable');
        } elseif (fseek($this->stream, $offset, $whence) === -1) {
            throw new \Exception(sprintf('Unable to seek to stream position %d with whence %s', $offset, var_export($whence, true)));
        }
    }

    public function rewind() {
        $this->seek(0);
    }

    public function write($string) {
        if (!$this->writable) {
            throw new \Exception('Stream is not writable');
        }

        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \Exception('Unable to write stream');
        }

        return $result;
    }

    public function read($length) {
        if (!$this->readable) {
            throw new \Exception('Stream is not readable');
        }

        return fread($this->stream, $length);
    }

    public function getContents() {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \Exception('Unable to read stream');
        }

        return $contents;
    }

    public function getMetadata($key = null) {
        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $key;
        }

        return isset($meta[$key]) ? $meta[$key] : null;
    }
}
