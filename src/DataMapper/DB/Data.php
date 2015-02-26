<?php
namespace Owl\DataMapper\DB;

class Data extends \Owl\DataMapper\Data {
    static protected $mapper = '\Owl\DataMapper\DB\Mapper';

    static public function select() {
        return static::getMapper()->select();
    }
}
