<?php
namespace Owl\Parameter;

/**
 * 参数有效性检查，检查参数是否存在，值是否符合要求
 *
 * @example
 * $checker = new \Owl\Parameter\Checker;
 * $checker->execute($vars, [
 *     'foo' => [                               // 通用配置
 *         'required' => (boolean),             // default true
 *         'allow_empty' => (boolean),          // default false
 *         'regexp' => (string),
 *         'eq' => (mixed),
 *         'same' => (mixed),
 *         'enum_eq' => [(mixed), ...],
 *         'enum_same' => [(mixed), ...],
 *         'callback' => function($value, array $option) {
 *             // ...
 *             return true;
 *         }
 *     ],
 *
 *     'foo' => [                               // 整数类型
 *         'type' => 'integer',
 *         'allow_negative' => (boolean),       // default true
 *     ],
 *
 *     'foo' => [                               // 浮点数类型
 *         'type' => 'float',
 *         'allow_negative' => (boolean),       // default true
 *     ],
 *
 *     'foo' => [
 *         'type' => 'ipv4',
 *     ],
 *
 *     'foo' => [
 *         'type' => 'uri',
 *     ],
 *
 *     'foo' => [
 *         'type' => 'url',
 *     ],
 *
 *     'foo' => [
 *         'type' => 'object',
 *         'instanceof' => (string),            // class name
 *     ],
 *
 *     'foo' => [
 *         'type' => 'array',                   // 普通数组
 *         'element' => [
 *             // ...
 *         ],
 *     ],
 *
 *     'foo' => [
 *         'type' => 'array',                   // hash数组
 *         'keys' => [
 *             // ...
 *         ],
 *     ],
 *
 *     'foo' => [
 *         'type' => 'json',
 *         'keys' => [
 *             // ...
 *         ],
 *     ],
 *
 *     'foo' => [
 *         'type' => 'json',
 *         'element' => [
 *             // ...
 *         ],
 *     ],
 * ]);
 */
class Checker {
    static public $types = [
        'integer' => [
            'regexp' => '/^\-?\d+$/',
            'allow_negative' => true,
        ],
        'numeric' => [
            'regexp' => '/^\-?\d+(?:\.\d+)?$/',
            'allow_negative' => true,
        ],
        'url' => [
            'regexp' => '#^[a-z]+://[0-9a-z\-\.]+\.[0-9a-z]{1,4}(?:\d+)?(?:/[^\?]*)?(?:\?[^\#]*)?(?:\#[0-9a-z\-\_\/]*)?$#',
        ],
        'uri' => [
            'regexp' => '#^/(?:[^?]*)?(?:\?[^\#]*)?(?:\#[0-9a-z\-\_\/]*)?$#',
        ],
        'ipv4' => [
            'regexp' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',
        ],
        'uuid' => [
            'regexp' => '/^[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}$/i',
        ],
    ];

    protected $path = [];

    public function execute(array $values, array $options) {
        foreach ($options as $key => $option) {
            $option = $this->normalizeOption($option);

            if (!array_key_exists($key, $values)) {
                if ($option['required']) {
                    throw $this->exception($key, 'required');
                }

                continue;
            }

            $this->check($key, $values[$key], $option);
        }

        return true;
    }

    protected function check($key, $value, array $option) {
        if ($value === '') {
            if ($option['allow_empty']) {
                return true;
            }

            throw $this->exception($key, 'not allow empty');
        }

        switch ($option['type']) {
            case 'hash':
            case 'array':
                return $this->checkArray($key, $value, $option);
            case 'json':
                return $this->checkJson($key, $value, $option);
            case 'object':
                return $this->checkObject($key, $value, $option);
            default:
                return $this->checkLiteral($key, $value, $option);
        }
    }

    protected function checkLiteral($key, $value, array $option) {
        if (isset($option['same'])) {
            if ($value === $option['same']) {
                return true;
            }

            throw $this->exception($key, sprintf('must strict equal [%s], current value is [%s]', $option['same'], $value));
        } elseif (isset($option['eq'])) {
            if ($value == $option['eq']) {
                return true;
            }

            throw $this->exception($key, sprintf('must equal [%s], current value is [%s]', $option['eq'], $value));
        } elseif (isset($option['enum_same'])) {
            if (in_array($value, $option['enum_same'], true)) {
                return true;
            }

            throw $this->exception($key, sprintf('must be strict equal one of [%s], current value is "%s"', implode(', ', $option['enum_same']), $value));
        } elseif (isset($option['enum_eq'])) {
            if (in_array($value, $option['enum_eq'])) {
                return true;
            }

            throw $this->exception($key, sprintf('must be equal one of [%s], current value is "%s"', implode(', ', $option['enum_eq']), $value));
        } elseif ($callback = $option['callback']) {
            if (!call_user_func_array($callback, [$value, $option])) {
                throw $this->exception($key, 'custom test failed');
            }
        } elseif ($regexp = $option['regexp']) {
            if (!preg_match($regexp, $value)) {
                throw $this->exception($key, sprintf('mismatch regexp %s, current value is "%s"', $regexp, $value));
            }
        }

        if ($option['type'] === 'bool' || $option['type'] === 'boolean') {
            if (!is_bool($value)) {
                throw $this->exception($key, sprintf('must be TRUE or FALSE, current value is "%s"', $value));
            }
        }

        if ($option['type'] === 'integer' || $option['type'] === 'numeric') {
            if ($value < 0 && !$option['allow_negative']) {
                throw $this->exception($key, sprintf('not allow negative numeric, current value is "%s"', $value));
            }
        }

        if (!$option['allow_tags'] && is_string($value)) {
            if (strip_tags($value) !== $value) {
                throw $this->exception($key, sprintf('content not allow tags, current value is "%s"', $value));
            }
        }

        return true;
    }

    protected function checkArray($key, $value, array $option) {
        if (!$value) {
            if ($option['allow_empty']) {
                return true;
            }

            throw $this->exception($key, 'not allow empty');
        }

        if (!is_array($value)) {
            throw $this->exception($key, 'is not array type');
        }

        if (!isset($option['keys']) && !isset($option['element'])) {
            throw $this->exception($key, 'rule missing "keys" or "element"');
        }

        if (isset($option['keys']) && $option['keys']) {
            $this->path[] = $key;

            $this->execute($value, $option['keys']);

            array_pop($this->path);
        } elseif (isset($option['element']) && $option['element']) {
            $this->path[] = $key;

            foreach ($value as $element) {
                $this->execute($element, $option['element']);
            }

            array_pop($this->path);
        }

        return true;
    }

    protected function checkJson($key, $value, array $option) {
        $value = json_decode($value, true);

        if ($value === null && ($error = json_last_error_msg())) {
            throw $this->exception($key, 'json_decode() failed, '. $error);
        }

        return $this->checkArray($key, $value, $option);
    }

    protected function checkObject($key, $value, array $option) {
        if (!is_object($value)) {
            throw $this->exception($key, 'is not object');
        }

        if (isset($option['instanceof']) && !($value instanceof $option['instanceof'])) {
            throw $this->exception($key, sprintf('must instanceof "%s"', $option['instanceof']));
        }

        return true;
    }

    private function normalizeOption(array $option) {
        if (isset($option['type'], self::$types[$option['type']])) {
            $option = array_merge(self::$types[$option['type']], $option);
        }

        $option = array_merge([
            'type' => null,
            'required' => true,
            'allow_empty' => false,
            'allow_tags' => false,
            'regexp' => null,
            'callback' => null,
        ], $option);

        return $option;
    }

    private function exception($key, $message) {
        $this->path[] = $key;
        $message = 'Key ['.implode('=>', $this->path).'], '.$message;

        $exception = new \Owl\Parameter\Exception($message);
        $exception->parameter = $key;

        return $exception;
    }
}
