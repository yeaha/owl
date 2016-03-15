<?php

namespace Tests\Mock\DataMapper;

class Data extends \Owl\DataMapper\Data
{
    protected static $mapper = '\Tests\Mock\DataMapper\Mapper';
    protected static $service = 'mock.storage';
    protected static $collection = 'mock.data';
    protected static $attributes = [
        'id' => ['type' => 'integer', 'primary_key' => true, 'auto_generate' => true],
    ];
}
