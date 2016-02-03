<?php
/**
 * @example
 *
 * $config = array(
 *     'request' => (\Owl\Http\Request),    // 必须
 *     'response' => (\Owl\Http\Response),  // 必须
 *     'token' => (string),                 // 必须，上下文存储唯一标识
 *     'sign_salt' => (mixed),              // 必须，用于计算数字签名的salt，可以为字符串或者callable方法
 *     'encrypt' => array(                  // 可选，加密方法配置
 *         (string),                        //   必须，salt string，随机字符串
 *         (string),                        //   可选，ciphers name，默认MCRYPT_RIJNDAEL_256
 *         (string),                        //   可选，ciphers mode, 默认MCRYPT_MODE_CBC
 *         (integer),                       //   可选，random device，默认自动匹配可用的
 *     ),
 *     'domain' => (string),                // 可选，cookie 域名，默认：null
 *     'path' => (string),                  // 可选，cookie 路径，默认：/
 *     'expire_at' => (integer),            // 可选，过期时间，优先级高于ttl
 *     'ttl' => (integer),                  // 可选，生存期，单位：秒，默认：0
 *     'bind_ip' => (bool),                 // 可选，是否绑定到IP，默认：false
 *     'zip' => (bool),                     // 可选，是否将数据压缩保存，默认：false
 * );
 *
 * $context = new \Owl\Context\Cookie($config);
 */
namespace Owl\Context;

class Cookie extends \Owl\Context {
    protected $data;
    protected $response;

    public function __construct(array $config) {
        (new \Owl\Parameter\Validator)->execute($config, [
            'request' => ['type' => 'object', 'instanceof' => '\Owl\Http\Request'],
            'response' => ['type' => 'object', 'instanceof' => '\Owl\Http\Response'],
        ]);

        parent::__construct($config);
    }

    public function set($key, $val) {
        $this->restore();

        $this->data[$key] = $val;
        $this->save();
    }

    public function get($key = null) {
        $data = $this->restore();

        return ($key === null)
             ? $data
             : (isset($data[$key]) ? $data[$key] : null);
    }

    public function has($key) {
        $data = $this->restore();

        return isset($data[$key]);
    }

    public function remove($key) {
        $this->restore();

        unset($this->data[$key]);
        $this->save();
    }

    public function clear() {
        $this->data = [];
        $this->save();
    }

    public function reset() {
        $this->data = null;
        $this->salt = null;
    }

    // 保存到cookie
    public function save() {
        $token = $this->getToken();
        $data = $this->data ? $this->encode($this->data) : '';
        if (!$expire = (int)$this->getConfig('expire_at'))
            $expire = ($ttl = (int)$this->getConfig('ttl')) ? (time() + $ttl) : 0;
        $path = $this->getConfig('path') ?: '/';
        $domain = $this->getConfig('domain');

        $this->getConfig('response')->setCookie($token, $data, $expire, $path, $domain);

        return $data;
    }

    // 从cookie恢复数据
    protected function restore() {
        if ($this->data !== null) {
            return $this->data;
        }

        do {
            if (!$data = $this->getConfig('request')->getCookieParam($this->getToken())) {
                break;
            }

            if (!$data = $this->decode($data)) {
                break;
            }

            return $this->data = $data;
        } while (false);

        return $this->data = [];
    }

    // 把上下文数据编码为字符串
    // return string
    protected function encode($data) {
        $data = json_encode($data);

        // 添加数字签名
        $data = $data . $this->getSign($data);

        if ($this->getConfig('encrypt')) {      // 加密，加密数据不需要压缩
            $data = $this->encrypt($data);
        } elseif ($this->getConfig('zip')) {    // 压缩
            // 压缩文本最前面有'_'，用于判断是否压缩数据
            // 否则在运行期间切换压缩配置时，错误的数据格式会导致gzcompress()报错
            $data = '_'. gzcompress($data, 9);
        }

        return base64_encode($data);
    }

    // 把保存为字符串的上下文数据恢复为数组
    // return array('c' => (array), 't' => (integer));
    protected function decode($string) {
        $string = base64_decode($string, true);
        if ($string === false) {
            return [];
        }

        if ($this->getConfig('encrypt')) {      // 解密
            $string = $this->decrypt($string);
        } elseif ($this->getConfig('zip')) {    // 解压
            $string = (substr($string, 0, 1) == '_')
                    ? gzuncompress(substr($string, 1))
                    : $string;
        }

        // sha1 raw binary length is 20
        $hash_length = 20;

        // 数字签名校验
        do {
            if (!$string || strlen($string) <= $hash_length) {
                break;
            }

            $hash = substr($string, $hash_length * -1);
            $string = substr($string, 0, strlen($string) - $hash_length);

            if ($this->getSign($string) !== $hash) {
                break;
            }

            return json_decode($string, true) ?: [];
        } while (false);

        return [];
    }

    // 加密字符串
    protected function encrypt($string) {
        list($salt, $cipher, $mode, $device) = $this->getEncryptConfig();

        $iv_size = mcrypt_get_iv_size($cipher, $mode);

        $salt = substr(md5($salt), 0, $iv_size);
        $iv = mcrypt_create_iv($iv_size, $device);

        $string = $this->pad($string);

        $encrypted = mcrypt_encrypt($cipher, $salt, $string, $mode, $iv);

        // 把iv保存和加密字符串在一起输出，解密的时候需要相同的iv
        return $iv . $encrypted;
    }

    // 解密字符串
    protected function decrypt($string) {
        list($salt, $cipher, $mode, $device) = $this->getEncryptConfig();

        $iv_size = mcrypt_get_iv_size($cipher, $mode);

        $salt = substr(md5($salt), 0, $iv_size);
        $iv = substr($string, 0, $iv_size);

        $string = substr($string, $iv_size);

        $decrypted = mcrypt_decrypt($cipher, $salt, $string, $mode, $iv);

        return $this->unpad($decrypted);
    }

    // 获得加密配置
    protected function getEncryptConfig() {
        $config = $this->getConfig('encrypt') ?: array();

        if (!isset($config[0]) || !$config[0]) {
            throw new \RuntimeException('Require encrypt salt string');
        }

        $salt = $config[0];

        $cipher = isset($config[1]) ? $config[1] : MCRYPT_RIJNDAEL_256;
        if (!in_array($cipher, mcrypt_list_algorithms())) {
            throw new \RuntimeException('Unsupport encrypt cipher: '. $cipher);
        }

        $mode = isset($config[2]) ? $config[2] : MCRYPT_MODE_CBC;
        if (!in_array($mode, mcrypt_list_modes())) {
            throw new \RuntimeException('Unsupport encrypt mode: '. $mode);
        }

        if (isset($config[3])) {
            $device = $config[3];
        } elseif (defined('MCRYPT_DEV_URANDOM')) {
            $device = MCRYPT_DEV_URANDOM;
        } elseif (defined('MCRYPT_DEV_RANDOM')) {
            $device = MCRYPT_DEV_RANDOM;
        } else {
            mt_srand();
            $device = MCRYPT_RAND;
        }

        return [$salt, $cipher, $mode, $device];
    }

    // 用PKCS7兼容字符串补全加密块
    protected function pad($string, $block = 32) {
        $pad = $block - (strlen($string) % $block);
        return $string . str_repeat(chr($pad), $pad);
    }

    // 去掉填充的PKCS7兼容字符串
    protected function unpad($string, $block = 32) {
        $pad = ord(substr($string, -1));

        if ($pad and $pad < $block) {
            if (!preg_match('/'.chr($pad).'{'.$pad.'}$/', $string)) {
                return false;
            }

            return substr($string, 0, strlen($string) - $pad);
        }

        return $string;
    }

    // 生成数字签名
    protected function getSign($string) {
        $salt = $this->getSignSalt($string);
        return sha1($string . $salt, true);
    }

    // 获得计算数字签名的salt字符串
    protected function getSignSalt($string) {
        if (($salt = $this->getConfig('sign_salt')) === null) {
            throw new \RuntimeException('Require signature salt');
        }

        if (is_callable($salt) && (!$salt = call_user_func($salt, $string))) {
            throw new \RuntimeException('Salt function return noting');
        }

        if ($this->getConfig('bind_ip')) {
            $ip = $this->getConfig('request')->getClientIP();
            $salt .= long2ip(ip2long($ip) & ip2long('255.255.255.0'));     // 192.168.1.123 -> 192.168.1.0
        }

        return $salt;
    }
}
