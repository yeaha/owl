<?php
namespace Tests\Context;

class CookieTest extends \PHPUnit_Framework_TestCase {
    protected function setUp() {
        \Tests\Mock\Cookie::getInstance()->reset();
    }

    public function testCookieContext() {
        $config_list = [
            '明文' => [
                'request' => \Owl\Http\Request::factory(),
                'response' => new \Tests\Mock\Http\Response,
                'token' => 'test',
                'sign_salt' => 'fdajkfldsjfldsf'
            ],
            '明文+压缩' => [
                'request' => \Owl\Http\Request::factory(),
                'response' => new \Tests\Mock\Http\Response,
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

            $handler = new \Owl\Context\Cookie(array_merge($config, [
                'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
                'response' => new \Tests\Mock\Http\Response,
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

        foreach ($crypt['ciphers'] as $cipher) {
            foreach ($crypt['mode'] as $mode) {
                $config = array_merge($config_default, [
                    'request' => \Owl\Http\Request::factory(),
                    'response' => new \Tests\Mock\Http\Response,
                    'encrypt' => ['uf43jrojfosdf', $cipher, $mode],
                ]);

                \Tests\Mock\Cookie::getInstance()->reset();

                $handler = new \Owl\Context\Cookie($config);
                $handler->set('test', 'abc 中文');

                $handler = new \Owl\Context\Cookie(array_merge($config, [
                    'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
                    'response' => new \Tests\Mock\Http\Response,
                ]));

                $this->assertEquals($handler->get('test'), 'abc 中文', "cipher:{$cipher} mode: {$mode} 加密解密失败");
            }
        }
    }

    // 数字签名
    public function testCookieContextSign() {
        $config = [
            'request' => \Owl\Http\Request::factory(),
            'response' => new \Tests\Mock\Http\Response,
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
        ];

        $handler = new \Owl\Context\Cookie($config);
        $handler->set('test', 'abc');

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
            'response' => new \Tests\Mock\Http\Response,
        ]));

        $handler->setConfig('sign_salt', 'r431oj0if31jr3');
        $this->assertNull($handler->get('test'), 'salt没有起作用');

        $cookies_data = $handler->getConfig('response')->getCookies();
        $cookies_data['test'] = '0'.$cookies_data['test'];

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $cookies_data]),
            'response' => new \Tests\Mock\Http\Response,
        ]));

        $this->assertNull($handler->get('test'), '篡改cookie内容');
    }

    // 从自定义方法内计算sign salt
    public function testCookieContextSignSaltFunc() {
        $salt_func = function($string) {
            $context = json_decode($string, true) ?: array();
            return isset($context['id']) ? $context['id'] : 'rj102jrojfoe';
        };

        $config = [
            'request' => \Owl\Http\Request::factory(),
            'response' => new \Tests\Mock\Http\Response,
            'token' => 'test',
            'sign_salt' => $salt_func,
        ];

        $handler = new \Owl\Context\Cookie($config);

        $id = uniqid();
        $handler->set('id', $id);

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies()]),
            'response' => new \Tests\Mock\Http\Response,
        ]));

        $this->assertEquals($id, $handler->get('id'), '自定义sign salt没有起作用');
    }

    // 地址绑定
    public function testBindIpCookieContext() {
        $config = [
            'request' => \Owl\Http\Request::factory([
                'ip' => '192.168.1.1',
            ]),
            'response' => new \Tests\Mock\Http\Response,
            'token' => 'test',
            'sign_salt' => 'fdajkfldsjfldsf',
            'bind_ip' => true
        ];

        $handler = new \Owl\Context\Cookie($config);
        $handler->set('test', 'abc');

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies(), 'ip' => '192.168.1.3']),
            'response' => new \Tests\Mock\Http\Response,
        ]));

        $this->assertEquals($handler->get('test'), 'abc', '同子网IP取值');

        $handler = new \Owl\Context\Cookie(array_merge($config, [
            'request' => \Owl\Http\Request::factory(['cookies' => $handler->getConfig('response')->getCookies(), 'ip' => '192.168.2.1']),
            'response' => new \Owl\Http\Response,
        ]));
        $this->assertNull($handler->get('test'), '不同子网IP取值');
    }
}
