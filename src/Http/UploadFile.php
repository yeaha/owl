<?php

namespace Owl\Http;

class UploadFile extends \Psr\Http\Message\UploadedFileInterface
{
    protected $moved;
    protected $file;

    public function __construct(array $file)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('File is invalid or not upload file via POST');
        }

        $this->file = $file;
    }

    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('File was moved to other directory');
        }

        return new \Owl\Http\ResourceStream(fopen($this->file['tmp_name'], 'r'));
    }

    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('File was moved to other directory');
        }

        if (!$target_path = realpath($targetPath)) {
            throw new \InvalidArgumentException('Invalid target path, '.$target_path);
        }

        $this->moved = true;

        $target = $targetPath.'/'.($this->getClientFilename() ?: $this->file['tmp_name']);
        if (!move_uploaded_file($this->file['tmp_name'], $target)) {
            throw new \RuntimeException('Unable to move upload file');
        }

        return $target;
    }

    public function getSize()
    {
        return isset($this->file['size']) ? $this->file['size'] : null;
    }

    public function getError()
    {
        return $this->file['error'];
    }

    public function getClientFilename()
    {
        return isset($this->file['name']) ? $this->file['name'] : null;
    }

    public function getClientMediaType()
    {
        return isset($this->file['type']) ? $this->file['type'] : null;
    }

    public function isError()
    {
        return $this->file['error'] !== UPLOAD_ERR_OK;
    }
}
