<?php

namespace Owl\Traits;

/**
 * @example
 * class Foo {
 *     public $message;
 *
 *     public function getMessage() {
 *         return $this->message;
 *     }
 *
 *     public function setMessage($message) {
 *         $this->message = $message;
 *     }
 * }
 *
 * class Bar {
 *     use \Owl\Traits\Decorator;
 *
 *     public function __construct() {
 *         $this->reference = new Foo;
 *     }
 * }
 *
 * $bar = new Bar;
 *
 * $bar->message = 'hello world!';
 * echo $bar->getMessage();
 * // OUTPUT: hello world!
 *
 * $bar->setMessage('foobar');
 * echo $bar->getMessage();
 * // OUTPUT: foobar
 */
trait Decorator
{
    protected $reference;

    public function __get($key)
    {
        return $this->getReference()->$key;
    }

    public function __set($key, $value)
    {
        $this->getReference()->$key = $value;
    }

    public function __call($method, array $args)
    {
        $reference = $this->getReference();

        return $args
             ? call_user_func_array([$reference, $method], $args)
             : $reference->$method();
    }

    protected function getReference()
    {
        if (!$this->reference) {
            throw new \Exception(get_class($this).': undefined reference object.');
        }

        return $this->reference;
    }
}
