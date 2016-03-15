<?php

namespace Owl\Http;

class Exception extends \Exception
{
    protected $body;
    protected $header = [];

    public function setHeader(array $header)
    {
        $this->header = $header;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public static function factory($status, \Exception $previous = null)
    {
        return new self(\Owl\Http::getStatusPhrase($status), $status, $previous);
    }
}
