<?php

namespace Tests\Mock\DataMapper\Mongo;

class Data extends \Owl\DataMapper\Mongo\Data
{
    protected static $mapper = '\Tests\Mock\DataMapper\Mongo\Mapper';
    protected static $attributes = [
        '_id' => ['primary_key' => true, 'auto_generate' => true],
    ];
}
