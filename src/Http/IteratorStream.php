<?php

namespace Owl\Http;

/**
 * @example
 * $output = function() use ($select) {
 *     foreach ($select->iterator() as $row) {
 *         $line = to_csv($row)."\n";
 *         yield $line;
 *     }
 * };
 *
 * $body = new \Owl\Http\IteratorStream($output());
 * $response->withBody($body);
 */
class IteratorStream extends \Owl\Http\Stream
{
    protected $position = 0;
    protected $seekable = false;
    protected $readable = false;
    protected $writable = false;

    public function __construct($iterator)
    {
        if (!($iterator instanceof \Iterator)) {
            throw new \Exception('Stream must be a Iterator object');
        }

        $this->stream = $iterator;
    }

    public function __toString()
    {
        try {
            return $this->getContents();
        } catch (\Exception $ex) {
            return '';
        }
    }

    public function iterator()
    {
        if ($this->eof()) {
            throw new \Exception('Stream was closed');
        }

        foreach ($this->stream as $result) {
            ++$this->position;
            yield $result;
        }
    }

    public function getContents()
    {
        $string = '';
        foreach ($this->iterator() as $result) {
            $string .= $result;
        }

        return $string;
    }

    public function tell()
    {
        return $this->position;
    }

    public function eof()
    {
        return !$this->stream->valid();
    }
}
