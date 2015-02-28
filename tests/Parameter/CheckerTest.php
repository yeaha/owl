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

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 'bar'), array('foo' => array('eq' => 'baz')));
    }

    public function testRegexp() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => 'baaaaaa'), array('foo' => array('regexp' => '/^ba+$/')));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'regexp' => '/^ba+$/')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => 'baaaaab'), array('foo' => array('regexp' => '/^ba+$/')));
    }

    public function testEnumValues() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '0'), array('foo' => array('enum' => array('0', '1'))));
        $checker->execute(array('foo' => ''), array('foo' => array('allow_empty' => true, 'enum' => array('0', '1'))));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '2'), array('foo' => array('allow_empty' => true, 'enum' => array('0', '1'))));
    }

    public function testIntegerType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '123'), array('foo' => array('type' => 'integer')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '12a'), array('foo' => array('type' => 'integer')));
    }

    public function testNumericType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '12.3'), array('foo' => array('type' => 'numeric')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '12.'), array('foo' => array('type' => 'numeric')));
    }

    public function testTelphoneType() {
        $checker = new \Owl\Parameter\Checker;

        $checker->execute(array('foo' => '13000000000'), array('foo' => array('type' => 'telphone')));

        $this->setExpectedException('\Owl\Parameter\Exception');
        $checker->execute(array('foo' => '23000000000'), array('foo' => array('type' => 'telphone')));
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
