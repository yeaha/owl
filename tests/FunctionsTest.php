<?php
namespace Tests;

class FunctionsTest extends \PHPUnit_Framework_TestCase {
    public function testStrHasTags() {
        $cases = [
            '<' => false,
            '<div' => false,
            '<a>' => true,
            '<a >' => true,
            '< a >' => false,
            '<h1>' => true,
            '<H1>' => true,
            '<a href>' => true,
            '<a href=>' => true,
            '<a href=/foo>' => true,
            '<a href=\'/foo\'>' => true,
            '<a href="/foo" target="_blank">' => true,
            '<测试>' => false,
            '<a测试>' => false,
            '<a 测试>' => true,
            '<script 测试 src="foo.js">' => true,
            '<script 测试= src="foo.js">' => true,
            '<script src="foo.js" 测试="">' => true,
            '<script src="foo.js">' => true,
        ];

        foreach ($cases as $case => $expect) {
            $this->assertSame($expect, \Owl\str_has_tags($case), $case);
        }
    }

    public function testArraySetIn() {
        $target = [];

        \Owl\array_set_in($target, ['a', 'b', 'c', 'd'], 1);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 1,
                    ],
                ]
            ],
        ], $target);

        \Owl\array_set_in($target, ['a', 'b', 'e'], 2);
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

        \Owl\array_set_in($target, ['f'], 3);
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

    public function testArrayPushIn() {
        $target = [];

        \Owl\array_push_in($target, ['a'], 1);
        $this->assertSame([
            'a' => [1],
        ], $target);

        \Owl\array_push_in($target, ['a'], 2);
        $this->assertSame([
            'a' => [1, 2],
        ], $target);

        \Owl\array_push_in($target, ['a', 'b'], 3);
        $this->assertSame([
            'a' => [
                1,
                2,
                'b' => [3],
            ],
        ], $target);

        \Owl\array_push_in($target, ['b', 'c', 'd'], 'e');
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
     * @depends testArraySetIn
     */
    public function testArrayGetIn(array $target) {
        $this->assertSame(3, \Owl\array_get_in($target, ['f']));
        $this->assertSame(1, \Owl\array_get_in($target, ['a', 'b', 'c', 'd']));
        $this->assertSame(2, \Owl\array_get_in($target, ['a', 'b', 'e']));
        $this->assertSame(['d' => 1], \Owl\array_get_in($target, ['a', 'b', 'c']));

        $this->assertFalse(\Owl\array_get_in($target, ['g']));
        $this->assertFalse(\Owl\array_get_in($target, ['a', 'c']));
        $this->assertFalse(\Owl\array_get_in($target, ['a', 'b', 'c', 'd', 'e']));

        return $target;
    }

    /**
     * @depends testArrayGetIn
     */
    public function testArrayUnsetIn(array $target) {
        \Owl\array_unset_in($target, ['a', 'b', 'e']);
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

        \Owl\array_unset_in($target, ['a', 'b', 'c', 'd']);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                    ],
                ]
            ],
            'f' => 3,
        ], $target);

        \Owl\array_unset_in($target, ['f']);
        $this->assertSame([
            'a' => [
                'b' => [
                    'c' => [
                    ],
                ]
            ],
        ], $target);
    }

    public function testArraySetInException() {
        $target = ['a' => ['b' => 1]];

        $this->setExpectedException('\RuntimeException');
        \Owl\array_set_in($target, ['a', 'b', 'c'], 2);
    }

    public function testArrayPushInException() {
        $target = ['a' => ['b' => 1]];

        $this->setExpectedException('\RuntimeException');
        \Owl\array_push_in($target, ['a', 'b'], 2);
    }

    public function testArrayTrim() {
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
        ], \Owl\array_trim($target));

        $target = [
            'a' => [null, 1, '', 'a'],
        ];

        $this->assertSame([
            'a' => [1, 'a'],
        ], \Owl\array_trim($target));
    }
}
