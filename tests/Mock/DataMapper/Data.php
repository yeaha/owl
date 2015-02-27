<?php
namespace Tests\Mock\DataMapper;

class Data extends \Owl\DataMapper\Data {
    static protected $mapper = '\Tests\Mock\DataMapper\Mapper';
    static protected $service = 'mock.storage';
    static protected $collection = 'mock.data';
    static protected $attributes = [
        'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
    ];
}
