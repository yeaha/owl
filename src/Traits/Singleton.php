<?php
namespace Owl\Traits;

// 单例模式
trait Singleton {
    static protected $__instances__ = array();

    protected function __construct() {}

    public function __clone() {
        throw new \Exception('Cloning '. __CLASS__ .' is not allowed');
    }

    static public function getInstance() {
        $class = get_called_class();

        if (!isset(static::$__instances__[$class])) {
            static::$__instances__[$class] = new static;
        }

        return static::$__instances__[$class];
    }

    static public function resetInstance() {
        $class = get_called_class();
        unset(static::$__instances__[$class]);
    }
}
