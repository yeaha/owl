<?php
namespace Owl\DataMapper\Mongo;

class Data extends \Owl\DataMapper\Data {
    static protected $mapper = '\Owl\DataMapper\Mongo\Mapper';

    static public function query($expr) {
        return static::getMapper()->query($expr);
    }

    static public function iterator($expr = null) {
        return static::getMapper()->iterator($expr);
    }
}
