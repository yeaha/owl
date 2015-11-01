<?php
namespace Tests\DataMapper;

use \Owl\DataMapper\Data;

class DataTest extends \PHPUnit_Framework_TestCase {
    protected $class = '\Tests\Mock\DataMapper\Data';

    protected function setAttributes(array $attributes) {
        $class = $this->class;
        $class::getMapper()->setAttributes($attributes);
    }

    public function testConstruct() {
        $class = $this->class;

        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'default' => 'foo'),
            'bar' => array('type' => 'string', 'default' => 'bar', 'allow_null' => true),
        ));

        $data = new $class;

        $this->assertTrue($data->isFresh());
        $this->assertTrue($data->isDirty());
        $this->assertEquals($data->foo, 'foo');
        $this->assertNull($data->bar);

        $data = new $class(array(
            'bar' => 'bar'
        ));

        $this->assertEquals($data->bar, 'bar');

        $data = new $class(array(), array('fresh' => false));

        $this->assertFalse($data->isFresh());
        $this->assertFalse($data->isDirty());
        $this->assertEquals('foo', $data->foo);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /primary key/
     */
    public function testBadConstruct() {
        $this->setAttributes(array());
    }

    public function testSetStrict() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'strict' => true),
        ));

        $data = new $this->class;

        $data->merge(array('foo' => 'foo'));
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', array('strict' => false));
        $this->assertFalse($data->isDirty('foo'));

        $data->set('foo', 'foo', array('strict' => true));
        $this->assertTrue($data->isDirty('foo'));

        $data->foo = 'bar';
        $this->assertEquals($data->foo, 'bar');
    }

    public function testSetRefuseUpdate() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'refuse_update' => true),
        ));

        $class = $this->class;

        $data = new $class;
        $data->foo = 'foo';

        $this->assertEquals($data->foo, 'foo');

        $data = new $class(array('foo' => 'foo'), array('fresh' => false));

        // test force set
        $data->set('foo', 'bar', array('force' => true));
        $this->assertEquals($data->foo, 'bar');

        $this->setExpectedExceptionRegExp('\Exception', '/refuse update/');
        $data->foo = 'foo';
    }

    public function testSetNull() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'string'),
        ));

        $data = new $this->class;

        $data->foo = null;

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UnexpectedPropertyValueException');
        $data->bar = null;
    }

    public function testSetSame() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'string'),
        ));

        $class = $this->class;
        $data = new $class(array('bar' => 'bar'), array('fresh' => false));

        $this->assertFalse($data->isDirty());

        $data->foo = null;
        $this->assertFalse($data->isDirty('foo'));

        $data->bar = 'bar';
        $this->assertFalse($data->isDirty('bar'));
    }

    public function testSetEmptyString() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'allow_null' => true),
            'bar' => array('type' => 'integer'),
        ));

        $data = new $this->class;

        $data->foo = '';
        $this->assertNull($data->foo);

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UnexpectedPropertyValueException');
        $data->bar = '';
    }

    public function testSetUndefined() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        ));

        $data = new $this->class;

        $data->set('bar', 'bar', array('strict' => false));
        $data->merge(array('bar' => 'bar'));

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UndefinedPropertyException');
        $data->bar = 'bar';
    }

    public function testGetUndefined() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        ));

        $data = new $this->class;

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\UndefinedPropertyException');
        $data->foo;
    }

    public function testGetObjectValue() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'time' => array('type' => 'datetime', 'default' => 'now')
        ));

        $data = new $this->class;
        $this->assertNotSame($data->time, $data->time);
    }

    public function testPick() {
        $this->setAttributes(array(
            'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
            'foo' => array('type' => 'string', 'protected' => true),
            'bar' => array('type' => 'string'),
        ));

        $class = $this->class;

        $data = new $class(array(
            'foo' => 'foo',
            'bar' => 'bar',
        ), array('fresh' => false));

        $values = $data->pick();

        $this->assertFalse(array_key_exists('id', $values));
        $this->assertFalse(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));

        $values = $data->pick('foo', 'bar', 'baz');

        $this->assertTrue(array_key_exists('foo', $values));
        $this->assertTrue(array_key_exists('bar', $values));
        $this->assertFalse(array_key_exists('baz', $values));
    }

    public function testGetID() {
        $class = $this->class;

        $this->setAttributes(array(
            'foo' => array('type' => 'string', 'primary_key' => true),
        ));

        $data = new $class(array('foo' => 'foo'));
        $this->assertEquals($data->id(), 'foo');
        $this->assertSame($data->id(true), ['foo' => 'foo']);

        $this->setAttributes(array(
            'foo' => array('type' => 'string', 'primary_key' => true),
            'bar' => array('type' => 'string', 'primary_key' => true),
        ));

        $data = new $class(array('foo' => 'foo', 'bar' => 'bar'));
        $this->assertSame($data->id(), array('foo' => 'foo', 'bar' => 'bar'));
    }

    public function testGetOptions() {
        $foo_options = \Tests\Mock\DataMapper\FooData::getOptions();

        $this->assertEquals($foo_options['service'], 'foo.service');
        $this->assertEquals($foo_options['collection'], 'foo.collection');
        $this->assertEquals(count($foo_options['attributes']), 2);
        $this->assertArrayHasKey('readonly', $foo_options);
        $this->assertArrayHasKey('strict', $foo_options);

        $bar_options = \Tests\Mock\DataMapper\BarData::getOptions();

        $this->assertEquals($bar_options['service'], 'bar.service');
        $this->assertEquals($bar_options['collection'], 'bar.collection');
        $this->assertEquals(count($bar_options['attributes']), 3);
    }

    public function testDeprecatedPrimaryKey() {
        $this->setExpectedExceptionRegExp('\RuntimeException', '/primary key/');

        $this->setAttributes(array(
            'id' => array('type' => 'string', 'primary_key' => true, 'deprecated' => true),
        ));
    }

    public function testDeprecatedAttribute() {
        $this->setAttributes(array(
            'id' => array('type' => 'string', 'primary_key' => true),
            'bar' => array('type' => 'string', 'deprecated' => true),
        ));

        $class = $this->class;
        $mapper = $class::getMapper();

        $attributes = $mapper->getAttributes();
        $this->assertArrayNotHasKey('bar', $attributes);

        $this->assertFalse($mapper->hasAttribute('bar'));

        $this->setExpectedExceptionRegExp('\Owl\DataMapper\Exception\DeprecatedPropertyException');
        $data = new $class;
        $bar = $data->bar;
    }

    public function testSetIn() {
        $this->setAttributes([
            'id' => ['type' => 'integer', 'primary_key' => true],
            'doc' => ['type' => 'json'],
            'msg' => ['type' => 'string'],
        ]);

        $class = $this->class;
        $data = new $class(['id' => 1], ['fresh' => false]);

        $this->assertFalse($data->isDirty('doc'));

        $data->setIn('doc', 'foo', 1);
        $this->assertSame(['foo' => 1], $data->get('doc'));
        $this->assertTrue($data->isDirty('doc'));

        $data->setIn('doc', 'bar', 2);
        $this->assertSame(['foo' => 1, 'bar' => 2], $data->get('doc'));

        $data->setIn('doc', ['foo', 'baz'], 3);
        $this->assertSame(['foo' => ['baz' => 3], 'bar' => 2], $data->get('doc'));

        try {
            $data->setIn('msg', 'foo', 1);
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }

        return $data;
    }

    /**
     * @depends testSetIn
     */
    public function testGetIn($data) {
        $this->assertSame(['baz' => 3], $data->getIn('doc', 'foo'));
        $this->assertSame(3, $data->getIn('doc', ['foo', 'baz']));
        $this->assertSame(2, $data->getIn('doc', 'bar'));

        $this->assertFalse($data->getIn('doc', 'foobar'));
        $this->assertFalse($data->getIn('doc', ['foo', 'bar']));

        try {
            $data->getIn('msg', 'foo');
        } catch (\Owl\DataMapper\Exception\UnexpectedPropertyValueException $ex) {
        }
    }
}

namespace Tests\Mock\DataMapper;

class FooData extends \Owl\DataMapper\Data {
    static protected $mapper_options = [
        'service' => 'foo.service',
        'collection' => 'foo.collection',
    ];
    static protected $attributes = array(
        'id' => array('type' => 'integer', 'primary_key' => true, 'auto_generate' => true),
        'foo' => array('type' => 'string'),
    );
}

class BarData extends FooData {
    static protected $mapper_options = [
        'service' => 'bar.service',
        'collection' => 'bar.collection',
    ];
    static protected $attributes = array(
        'bar' => array('type' => 'string'),
    );
}
