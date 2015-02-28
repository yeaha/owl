<?php
namespace Owl;

/**
 * @example
 * use Owl\Config;
 *
 * Config::merge([
 *     'foo' => [
 *         'bar' => [
 *             'baz' => 1,
 *         ]
 *     ]
 * ]);
 *
 * Config::get();
 * Config::get('foo');
 * Config::get('foo', 'bar');
 * Config::get('foo', 'bar', 'baz');
 * Config::get(['foo', 'bar', 'baz']);
 */
class Config {
    static private $config = [];

    static public function merge(array $config) {
        self::$config = array_merge(self::$config, $config);
    }

    static public function get($key = null) {
        $keys = $keys === null
              ? null
              : is_array($keys) ? $keys : func_get_args();

        if ($keys === null) {
            return self::$config;
        }

        $config = &self::$config;

        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                return false;
            }

            $config = &$config[$key];
        }

        return $config;
    }
}
