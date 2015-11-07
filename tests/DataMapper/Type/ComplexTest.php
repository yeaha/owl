<?php
namespace Tests\DataMapper\Type;

use \Owl\DataMapper\Type\Complex;

class ComplexTest extends \PHPUnit_Framework_TestCase {
    public function testSetIn() {
        $target = [];

        Complex::setIn($target, ['a', 'b', 'c', 'd'], 1);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 1,
                    ],
                ]
            ],
        ], $target);

        Complex::setIn($target, ['a', 'b', 'e'], 2);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 1,
                    ],
                    'e' => 2,
                ]
            ],
        ], $target);

        Complex::setIn($target, ['f'], 3);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 1,
                    ],
                    'e' => 2,
                ]
            ],
            'f' => 3,
        ], $target);

        return $target;
    }

    public function testPushIn() {
        $target = [];

        Complex::pushIn($target, ['a'], 1);
        $this->assertSame([
            'a' => [1],
        ], $target);

        Complex::pushIn($target, ['a'], 2);
        $this->assertSame([
            'a' => [1, 2],
        ], $target);

        Complex::pushIn($target, ['a', 'b'], 3);
        $this->assertSame([
            'a' => [
                1,
                2,
                'b' => [3],
            ],
        ], $target);

        Complex::pushIn($target, ['b', 'c', 'd'], 'e');
        $this->assertSame([
            'a' => [
                1,
                2,
                'b' => [3],
            ],
            'b' => [
                'c' => [
                    'd' => ['e']
                ]
            ],
        ], $target);
    }

    /**
     * @depends testSetIn
     */
    public function testGetIn(array $target) {
        $this->assertSame(3, Complex::getIn($target, ['f']));
        $this->assertSame(1, Complex::getIn($target, ['a', 'b', 'c', 'd']));
        $this->assertSame(2, Complex::getIn($target, ['a', 'b', 'e']));
        $this->assertSame(['d' => 1], Complex::getIn($target, ['a', 'b', 'c']));

        $this->assertFalse(Complex::getIn($target, ['g']));
        $this->assertFalse(Complex::getIn($target, ['a', 'c']));
        $this->assertFalse(Complex::getIn($target, ['a', 'b', 'c', 'd', 'e']));

        return $target;
    }

    /**
     * @depends testGetIn
     */
    public function testUnsetIn(array $target) {
        Complex::unsetIn($target, ['a', 'b', 'e']);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 1,
                    ],
                ]
            ],
            'f' => 3,
        ], $target);

        Complex::unsetIn($target, ['a', 'b', 'c', 'd']);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                    ],
                ]
            ],
            'f' => 3,
        ], $target);

        Complex::unsetIn($target, ['f']);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                    ],
                ]
            ],
        ], $target);
    }

    public function testSetInException() {
        $target = ['a' => ['b' => 1]];

        $this->setExpectedException('\RuntimeException');
        Complex::setIn($target, ['a', 'b', 'c'], 2);
    }

    public function testPushInException() {
        $target = ['a' => ['b' => 1]];

        $this->setExpectedException('\RuntimeException');
        Complex::pushIn($target, ['a', 'b'], 2);
    }

    public function testTrim() {
        $target = [
            'a' => 1,
            'b' => null,
            'c' => [
                'd' => 1,
                'e' => [],
                'f' => [
                    'g' => null,
                ],
                'h' => [
                    'i' => '',
                ],
                'j' => [
                    'k' => [],
                    'l' => 1,
                ],
            ],
        ];

        $this->assertSame([
            'a' => 1,
            'c' => [
                'd' => 1,
                'j' => [
                    'l' => 1,
                ],
            ],
        ], Complex::trim($target));

        $target = [
            'a' => [null, 1, '', 'a'],
        ];

        $this->assertSame([
            'a' => [1, 'a'],
        ], Complex::trim($target));
    }
}
