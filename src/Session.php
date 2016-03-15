<?php

namespace Owl;

class Session implements \ArrayAccess
{
    use \Owl\Traits\Singleton;

    protected $start;
    protected $data = array();
    protected $snapshot = array();

    protected function __construct()
    {
        $this->start = (session_status() === PHP_SESSION_ACTIVE);

        if ($this->start) {
            $this->data = $_SESSION instanceof self
                        ? $_SESSION->toArray()
                        : $_SESSION;
        }

        $this->snapshot = $this->data;
    }

    public function offsetExists($offset)
    {
        $this->start();

        return isset($this->data[$offset]);
    }

    // 返回引用，否则会发生"Indirect modification of overloaded element of $class has no effect"错误
    public function &offsetGet($offset)
    {
        $this->start();

        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->start();
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $this->start();
        unset($this->data[$offset]);
    }

    public function commit()
    {
        if (!$this->start) {
            return false;
        }

        $_SESSION = $this->data;
        session_write_close();

        $this->snapshot = $this->data;
        $_SESSION = $this;

        $this->start = (session_status() === PHP_SESSION_ACTIVE);
    }

    public function reset()
    {
        $this->data = $this->snapshot;
        $this->start = (session_status() === PHP_SESSION_ACTIVE);
    }

    public function destroy()
    {
        if ($this->start()) {
            session_destroy();
        }

        $this->reset();
    }

    public function start()
    {
        if ($this->start) {
            return true;
        }

        if (session_status() === PHP_SESSION_DISABLED) {
            return false;
        }

        @session_start();
        $this->data = $_SESSION;
        $this->snapshot = $_SESSION;

        $_SESSION = $this;

        return $this->start = true;
    }

    public function toArray()
    {
        return $this->data;
    }

    //////////////////// static method ////////////////////

    public static function initialize()
    {
        if (!isset($GLOBALS['_SESSION']) or !($GLOBALS['_SESSION'] instanceof self)) {
            $GLOBALS['_SESSION'] = self::getInstance();
        }

        return self::getInstance();
    }
}
