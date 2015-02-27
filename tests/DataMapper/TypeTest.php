<?php
namespace Tests;

use \Owl\DataMapper;

class TypeTest extends \PHPUnit_Framework_TestCase {
    public function testNormalizeAttribute() {
        $attribute = DataMapper\Type::normalizeAttribute(array('primary_key' => true));

        $this->assertFalse($attribute['allow_null']);
        $this->assertTrue($attribute['refuse_update']);
        $this->assertTrue($attribute['strict']);

        $attribute = DataMapper\Type::normalizeAttribute(array('protected' => true));

        $this->assertTrue($attribute['strict']);

        $attribute = DataMapper\Type::normalizeAttribute(array('default' => 'foo', 'allow_null' => true));
        $this->assertNull($attribute['default']);
    }

    public function testMixed() {
        $type = $this->getType(null);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $type = $this->getType('undefined type name');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $attribute = array('foo' => 'bar');
        $this->assertSame($attribute, $type->normalizeAttribute($attribute));
        $this->assertSame('foo', $type->normalize('foo', array()));
        $this->assertSame('foo', $type->store('foo', array()));
        $this->assertSame('foo', $type->restore('foo', array()));
        $this->assertSame('foo', $type->toJSON('foo', array()));

        $this->assertSame('foo', $type->getDefaultValue(array('default' => 'foo')));
    }

    public function testNumeric() {
        $type = $this->getType('numeric');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Numeric', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $this->assertSame(1.11, $type->normalize('1.11', array()));
    }

    public function testInteger() {
        $type = $this->getType('integer');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Integer', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $this->assertSame(1, $type->normalize('1.11', array()));
    }

    public function testString() {
        $type = $this->getType('string');
        $this->assertInstanceOf('\Owl\DataMapper\Type\String', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $this->assertSame('1.11', $type->normalize(1.11, array()));
    }

    public function testUUID() {
        $type = $this->getType('uuid');
        $this->assertInstanceOf('\Owl\DataMapper\Type\UUID', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $attribute = $type->normalizeAttribute(array('primary_key' => true));
        $this->assertTrue($attribute['auto_generate']);

        $re = '/^[0-9A-F\-]{36}$/';
        $this->assertRegExp($re.'i', $type->getDefaultValue(array('auto_generate' => true)));
        $this->assertRegExp($re, $type->getDefaultValue(array('auto_generate' => true, 'upper' => true)));
    }

    public function testDateTime() {
        $type = $this->getType('datetime');
        $this->assertInstanceOf('\Owl\DataMapper\Type\Datetime', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $now = new \Datetime;
        $this->assertSame($now, $type->normalize($now, array()));

        $this->assertInstanceOf('\Datetime', $type->normalize('now', array()));

        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2}[+\-]\d{1,2}(?::\d{1,2})?$/', $type->store($now, array()));
        $this->assertRegExp('/^\d{4}\-\d{1,2}\-\d{1,2}$/', $type->store($now, array('format' => 'Y-m-d')));
        $this->assertRegExp('/^\d+$/', $type->store($now, array('format' => 'U')));

        $this->assertInstanceOf('\Datetime', $type->restore('2014-01-01T00:00:00+0', array()));

        $ts = 1388534400;
        $time = $type->restore($ts, array('format' => 'U'));

        $this->assertInstanceOf('\Datetime', $time);
        $this->assertEquals($ts, $time->getTimestamp());

        $this->setExpectedException('\UnexpectedValueException');
        $type->normalize($ts, array('format' => 'c'));
    }

    public function testJSON() {
        $attribute = DataMapper\Type::normalizeAttribute(array('type' => 'json'));

        $this->assertTrue($attribute['strict']);

        $type = $this->getType('json');
        $this->assertInstanceOf('\Owl\DataMapper\Type\JSON', $type);
        $this->assertInstanceOf('\Owl\DataMapper\Type\Mixed', $type);

        $json = array('foo' => 'bar');
        $this->assertEquals($json, $type->normalize($json, array()));
        $this->assertEquals($json, $type->normalize(json_encode($json), array()));

        $this->assertNull($type->store(array(), array()));
        $this->assertEquals(json_encode($json), $type->store($json, array()));

        $this->setExpectedException('\UnexpectedValueException');
        $type->restore('{"a"', array());

        $this->assertSame(array(), $type->getDefaultValue(array()));
        $this->assertSame(array(), $type->getDefaultValue(array('allow_null' => true)));
    }

    public function testRestoreNull() {
        $expect = array(
            'mixed' => null,
            'string' => null,
            'integer' => null,
            'numerci' => null,
            'uuid' => null,
            'datetime' => null,
            'json' => array(),
            'pg_array' => array(),
            'pg_hstore' => array(),
        );

        foreach ($expect as $type => $value) {
            $this->assertSame($value, $this->getType($type)->restore(null, array()));
        }
    }

    protected function getType($name) {
        return DataMapper\Type::getInstance()->get($name);
    }
}
