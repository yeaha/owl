<?php
namespace Tests;

class ContextTest extends \PHPUnit_Framework_TestCase {
    protected function createHandler($type, $config) {
        return \Owl\Context::factory($type, $config);
    }

    public function testCookieContext() {
        $config_list = [
            '明文' => [
                'request' => \Owl\Http\Request::factory(),
                'response' => new \Owl\Http\Response,
                'token' => 'test',
                'sign_salt' => 'fdajkfldsjfldsf'
            ],
            '明文+压缩' => [
                'request' => \Owl\Http\Request::factory(),
                'response' => new \Owl\Http\Response,
                'token' => 'test',
                'sign_salt' => 'fdajkfldsjfldsf',
                'zip' => true,
            ],
        ];

        $cookies = \Tests\Mock\Cookie::getInstance();
        foreach ($config_list as $msg => $config) {
            $cookies->reset();

            $handler = new \Owl\Context\Cookie($config);
            $handler->set('test', 'abc 中文');

            $cookies->apply($handler->getConfig('response'));

            $handler = new \Owl\Context\Cookie(array_merge($config, [
                'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get()]),
                'response' => new \Owl\Http\Response,
            ]));

            $this->assertEquals($handler->get('test'), 'abc 中文', $msg);
        }
    }

    public function testCookieEncrypt() {
        if (!extension_loaded('mcrypt')) {
            $this->markTestSkipped('没有加载mcrypt模块，无法测试cookie加密功能');
        }

        $crypt = array(
            'ciphers' => array(MCRYPT_RIJNDAEL_256, MCRYPT_BLOWFISH, MCRYPT_CAST_256),
            'mode' => array(MCRYPT_MODE_ECB, MCRYPT_MODE_CBC, MCRYPT_MODE_CFB, MCRYPT_MODE_OFB, MCRYPT_MODE_NOFB),
        );

        $config_default = array(
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
        );

        $cookies = \Tests\Mock\Cookie::getInstance();
        foreach ($crypt['ciphers'] as $cipher) {
            foreach ($crypt['mode'] as $mode) {
                $config = array_merge($config_default, [
                    'request' => \Owl\Http\Request::factory(),
                    'response' => new \Owl\Http\Response,
                    'encrypt' => ['uf43jrojfosdf', $cipher, $mode],
                ]);

                $cookies->reset();

                $handler = new \Owl\Context\Cookie($config);
                $handler->set('test', 'abc 中文');

                $cookies->apply($handler->getConfig('response'));

                $handler = new \Owl\Context\Cookie(array_merge($config, [
                    'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get()]),
                    'response' => new \Owl\Http\Response,
                ]));

                $this->assertEquals($handler->get('test'), 'abc 中文', "cipher:{$cipher} mode: {$mode} 加密解密失败");
            }
        }
    }

    // 数字签名
    public function testCookieContextSign() {
        $cookies = \Tests\Mock\Cookie::getInstance();
        $cookies->reset();

        $config = [
            'request' => \Owl\Http\Request::factory(),
            'response' => new \Owl\Http\Response,
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
        ];

        $handler = new \Owl\Context\Cookie($config);
        $handler->set('test', 'abc');
        $cookies->apply($handler->getConfig('response'));

        $cookies_data = $cookies->get();
        $cookies_data['test'] = '0'.$cookies_data['test'];

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies_data]),
            'response' => new \Owl\Http\Response,
        ]));

        $this->assertNull($handler->get('test'), '篡改cookie内容');

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get()]),
            'response' => new \Owl\Http\Response,
        ]));

        $handler->setConfig('sign_salt', 'r431oj0if31jr3');
        $this->assertNull($handler->get('test'), 'salt没有起作用');
    }

    // 从自定义方法内计算sign salt
    public function testCookieContextSignSaltFunc() {
        $cookies = \Tests\Mock\Cookie::getInstance();
        $cookies->reset();

        $salt_func = function($string) {
            $context = json_decode($string, true) ?: array();
            return isset($context['id']) ? $context['id'] : 'rj102jrojfoe';
        };

        $config = [
            'request' => \Owl\Http\Request::factory(),
            'response' => new \Owl\Http\Response,
            'token' => 'test',
            'sign_salt' => $salt_func,
        ];

        $handler = new \Owl\Context\Cookie($config);

        $id = uniqid();
        $handler->set('id', $id);

        $cookies->apply($handler->getConfig('response'));

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get()]),
            'response' => new \Owl\Http\Response,
        ]));

        $this->assertEquals($id, $handler->get('id'), '自定义sign salt没有起作用');
    }

    // 地址绑定
    public function testBindIpCookieContext() {
        $cookies = \Tests\Mock\Cookie::getInstance();
        $cookies->reset();

        $config = [
            'request' => \Owl\Http\Request::factory([
                'ip' => '192.168.1.1',
            ]),
            'response' => new \Owl\Http\Response,
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
            'bind_ip' => true
        ];

        $handler = new \Owl\Context\Cookie($config);
        $handler->set('test', 'abc');

        $cookies->apply($handler->getConfig('response'));

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get(), 'ip' => '192.168.1.3']),
            'response' => new \Owl\Http\Response,
        ]));

        $this->assertEquals($handler->get('test'), 'abc', '同子网IP取值');

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies->get(), 'ip' => '192.168.2.1']),
            'response' => new \Owl\Http\Response,
        ]));
        $this->assertNull($handler->get('test'), '不同子网IP取值');
    }

    public function testRedisContext() {
        $config = [
            'token' => uniqid(),
            'ttl' => 300,
            'service' => new \Owl\Service\Redis,
        ];
        $handler = new \Tests\Mock\Context\Redis($config);

        $this->assertSame(array(), $handler->get());
        $this->assertFalse($handler->isDirty());

        $handler->set('foo', 1);
        $this->assertTrue($handler->isDirty());

        $handler->set('bar', 2);
        $this->assertTrue($handler->save());
        $this->assertFalse($handler->isDirty());

        $ttl = $handler->getTimeout();
        $this->assertTrue($ttl && $ttl > 0);

        $handler = new \Tests\Mock\Context\Redis($config);
        $this->assertEquals(1, $handler->get('foo'));
        $this->assertEquals(2, $handler->get('bar'));

        $handler->remove('bar');
        $this->assertTrue($handler->isDirty());
        $this->assertTrue($handler->save());

        $handler = new \Tests\Mock\Context\Redis($config);
        $this->assertTrue($handler->has('foo'));
        $this->assertFalse($handler->has('bar'));

        $handler = new \Tests\Mock\Context\Redis($config);
        $handler->set('foo', '1');
        $this->assertFalse($handler->isDirty());
        $handler->set('foo', 2);
        $this->assertTrue($handler->isDirty());

        $handler = new \Tests\Mock\Context\Redis($config);
        $handler->remove('foobar');
        $this->assertFalse($handler->isDirty());
        $handler->remove('foo');
        $this->assertTrue($handler->isDirty());

        $handler->clear();
        $handler->save();
    }
}
