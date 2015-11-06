<?php
namespace Tests\Mock\DataMapper\Mongo;

class Data extends \Owl\DataMapper\Mongo\Data {
    static protected $mapper = '\Tests\Mock\DataMapper\Mongo\Mapper';
    static protected $attributes = [
        '_id' => ['primary_key' => true, 'auto_generate' => true],
    ];
}
