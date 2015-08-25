<?php
namespace Tests\Parameter;

class CheckerTest extends \PHPUnit_Framework_TestCase {
    public function testRequired() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 'bar'), array('foo' => array('required' => true)));
        $checker->execute(array(), array('foo' => array('required' => false)));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array(), array('foo' => array('required' => true)));
    }

    public function testAllowEmpty() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true)));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => false)));
    }

    public function testEquals() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 'bar'), array('foo' => array('eq' => 'bar')));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'eq' => 'bar')));

        $checker->execute(array('foo' => '1'), array('foo' => array('type' => 'integer', 'eq' => '1')));
        $checker->execute(array('foo' => '1'), array('foo' => array('type' => 'integer', 'eq' => 1)));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 'bar'), array('foo' => array('eq' => 'baz')));
    }

    public function testSame() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 1), array('foo' => array('type' => 'integer', 'same' => 1)));

        try {
            $checker->execute(array('foo' => '1'), array('foo' => array('type' => 'integer', 'same' => 1)));
            $this->fail('compire same type, test failed');
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }
    }

    public function testRegexp() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 'baaaaaa'), array('foo' => array('regexp' => '/^ba+$/')));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'regexp' => '/^ba+$/')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 'baaaaab'), array('foo' => array('regexp' => '/^ba+$/')));
    }

    public function testEnumEqualValues() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '0'), array('foo' => array('enum_eq' => array(0, '1'))));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'enum_eq' => array('0', '1'))));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '2'), array('foo' => array('allow_empty' => true, 'enum_eq' => array('0', '1'))));
    }

    public function testEnumSameValues() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 0), array('foo' => array('enum_same' => array(0, 1))));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'enum_same' => array(0, 1))));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '1'), array('foo' => array('allow_empty' => true, 'enum_same' => array(0, 1))));
    }

    public function testAllowTags() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(['foo' => 'normal string'], ['foo' => ['type' => 'string']]);

        try {
            $checker->execute(['foo' => '<script></script>'], ['foo' => ['type' => 'string']]);
            $this->fail('test string "allow_tags" failed');
        } catch (\Owl\Parameter\Exception $exception) {
            $this->assertTrue(true);
        }

        $checker->execute(['foo' => '<script></script>'], ['foo' => ['type' => 'string', 'allow_tags' => true]]);
    }

    public function testIntegerType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '123'), array('foo' => array('type' => 'integer')));

        try {
            $checker->execute(array('foo' => '12a'), array('foo' => array('type' => 'integer')));
            $this->fail('integer type test failed');
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }

        try {
            $checker->execute(array('foo' => -1), array('foo' => array('type' => 'integer', 'allow_negative' => false)));
            $this->fail('integer type, negative test failed');
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }
    }

    public function testNumericType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '12.3'), array('foo' => array('type' => 'numeric')));

        try {
            $checker->execute(array('foo' => '12.'), array('foo' => array('type' => 'numeric')));
            $this->fail('numeric type, test failed');
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }

        try {
            $checker->execute(array('foo' => -1.2), array('foo' => array('type' => 'numeric', 'allow_negative' => false)));
            $this->fail('numeric type, negative test failed');
        } catch (\Owl\Parameter\Exception $ex) {
            $this->assertTrue(true);
        }
    }

    public function testBoolType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => true), array('foo' => array('type' => 'bool')));
        $checker->execute(array('foo' => false), array('foo' => array('type' => 'bool')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 0), array('foo' => array('type' => 'bool')));
    }

    public function testHashType() {
        $checker = new \Owl\Parameter\Checker;

        $options = array(
            'foo' => array(
                'type' => 'hash',
                'keys' => array(
                    'bar' => array(
                        'type' => 'integer',
                    )
                )
            ),
        );

        $checker->execute(
            array('foo' => array('bar' => '1')),
            $options
        );

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => array('bar' => 'a')), $options);
    }

    public function testHashTypeException() {
        $checker = new \Owl\Parameter\Checker;

        $options = array(
            'foo' => array(
                'type' => 'hash',
            ),
        );

        $this->setExpectedExceptionRegExp('\Owl\Parameter\Exception', '/is not hash type/');
        $checker->execute(
            array('foo' => array(1, 2, 3)),
            $options
        );
    }

    public function testEmptyHashException() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(
            array('foo' => array()),
            array(
                'foo' => array(
                    'type' => 'hash',
                    'allow_empty' => true,
                ),
            )
        );

        $this->setExpectedExceptionRegExp('\Owl\Parameter\Exception', '/not allow empty hash/');
        $checker->execute(
            array('foo' => array()),
            array(
                'foo' => array(
                    'type' => 'hash',
                ),
            )
        );
    }

    public function testArrayType() {
        $checker = new \Owl\Parameter\Checker;

        $options = array(
            'foo' => array(
                'type' => 'array',
                'allow_empty' => true,
                'element' => array(
                    'bar' => array(
                        'type' => 'integer',
                    )
                )
            ),
        );

        $checker->execute(array(
            'foo' => array(
                array(
                    'bar' => '1',
                )
            )
        ), $options);

        $checker->execute(array(
            'foo' => array(
            )
        ), $options);

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array(
            'foo' => array(
                array(
                    'bar' => 'a',
                )
            )
        ), $options);
    }

    public function testEmptyArrayException() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(
            array('foo' => array()),
            array(
                'foo' => array(
                    'type' => 'array',
                    'allow_empty' => true,
                ),
            )
        );

        $this->setExpectedExceptionRegExp('\Owl\Parameter\Exception', '/not allow empty array/');
        $checker->execute(
            array('foo' => array()),
            array(
                'foo' => array(
                    'type' => 'array',
                ),
            )
        );
    }

    public function testURLType() {
        $checker = new \Owl\Parameter\Checker;

        $options = array(
            'foo' => array(
                'type' => 'url',
            ),
        );

        $checker->execute(array('foo' => 'http://192.168.1.1/'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/#'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/foo/bar'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/foo/bar?'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/foo/bar?a=b'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/foo/bar?a=b#'), $options);
        $checker->execute(array('foo' => 'http://192.168.1.1/foo/bar?a=b#c/d'), $options);

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '/foo/bar?a=b#c/d'), $options);
    }

    public function testURIType() {
        $checker = new \Owl\Parameter\Checker;

        $options = array(
            'foo' => array(
                'type' => 'uri',
            ),
        );

        $checker->execute(array('foo' => '/'), $options);
        $checker->execute(array('foo' => '/?'), $options);
        $checker->execute(array('foo' => '/foo/bar'), $options);
        $checker->execute(array('foo' => '/foo/bar?'), $options);
        $checker->execute(array('foo' => '/foo/bar?a=b'), $options);
        $checker->execute(array('foo' => '/foo/bar?a=b#'), $options);
        $checker->execute(array('foo' => '/foo/bar?a=b#c/d'), $options);

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 'foo/bar'), $options);
    }
}
