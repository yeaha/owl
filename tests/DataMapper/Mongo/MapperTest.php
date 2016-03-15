<?php

namespace Tests\DataMapper\Mongo;

class MapperTest extends \PHPUnit_Framework_TestCase
{
    protected $class = '\Tests\Mock\DataMapper\Mongo\Data';

    protected function setAttributes(array $attributes)
    {
        $class = $this->class;
        $class::getMapper()->setAttributes($attributes);
    }

    protected function newData(array $values = [], array $options = [])
    {
        $class = $this->class;

        return new $class($values, $options);
    }

    public function testUnpack()
    {
        $this->setAttributes([
            '_id' => ['primary_key' => true],
            'foo' => ['type' => 'string', 'allow_null' => true],
            'bar' => ['type' => 'complex', 'allow_null' => true],
        ]);

        $class = $this->class;
        $mapper = $class::getMapper();

        $data = $this->newData([
            'foo' => 'foo',
        ]);
        $data->setIn('bar', 'a', []);

        $record = $mapper->unpack($data);
        $this->assertSame(['a' => []], $data->bar);
        $this->assertSame(['foo' => 'foo'], $mapper->unpack($data));

        $data = $mapper->pack(['foo' => 'foo']);
        $data->setIn('bar', 'a', []);

        $this->assertSame(['a' => []], $data->bar);
        $this->assertSame(['foo' => 'foo', 'bar' => null], $mapper->unpack($data));
    }
}
