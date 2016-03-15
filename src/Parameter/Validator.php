<?php

namespace Owl\Parameter;

/**
 * 参数有效性检查，检查参数是否存在，值是否符合要求
 *
 * @example
 * $validator = new \Owl\Parameter\Validator;
 *
 * $validator->execute($vars, [
 *     'foo' => [                               // 通用配置
 *         'required' => (boolean),             // default true
 *         'allow_empty' => (boolean),          // default false
 *         'regexp' => (string),
 *         'eq' => (mixed),
 *         'same' => (mixed),
 *         'enum_eq' => [(mixed), ...],
 *         'enum_same' => [(mixed), ...],
 *         'callback' => function($value, $key, array $rule) {
 *             // ...
 *             return true;
 *         }
 *     ],
 *
 *     'foo' => [                               // 整数类型
 *         'type' => 'integer',
 *         'allow_negative' => (boolean),       // default false
 *         'allow_zero' => (boolean),           // default true
 *     ],
 *
 *     'foo' => [                               // 浮点数类型
 *         'type' => 'float',
 *         'allow_negative' => (boolean),       // default true
 *         'allow_zero' => (boolean),           // default true
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
 *         'value' => [
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
 *         'type' => 'array',
 *         'value' => [                         // 对数组的元素进行检查
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
 *         'value' => [
 *             // ...
 *         ],
 *     ],
 * ]);
 */
class Validator
{
    public static $types = [
        'integer' => [
            'regexp' => '/^\-?\d+$/',
            'allow_negative' => false,
            'allow_zero' => true,
        ],
        'numeric' => [
            'regexp' => '/^\-?\d+(?:\.\d+)?$/',
            'allow_negative' => false,
            'allow_zero' => true,
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

    private $path = [];

    public function execute(array $values, array $rules)
    {
        foreach ($rules as $key => $rule) {
            $rule = $this->normalizeRule($rule);

            if (!array_key_exists($key, $values)) {
                if ($rule['required']) {
                    throw $this->exception($key, 'required');
                }

                continue;
            }

            $value = $values[$key];
            if ($value === '' || $value === [] || $value === null) {
                if (!$rule['allow_empty']) {
                    throw $this->exception($key, 'not allow empty');
                }

                continue;
            }

            $this->check($key, $values[$key], $rule);
        }

        return true;
    }

    protected function check($key, $value, array $rule)
    {
        if (!isset($rule['__normalized__'])) {
            $rule = $this->normalizeRule($rule);
        }

        switch ($rule['type']) {
            case 'array':
                return $this->checkArray($key, $value, $rule);
            case 'json':
                return $this->checkJson($key, $value, $rule);
            case 'object':
                return $this->checkObject($key, $value, $rule);
            default:
                return $this->checkScalar($key, $value, $rule);
        }
    }

    protected function checkScalar($key, $value, array $rule)
    {
        if (isset($rule['same'])) {
            if ($value === $rule['same']) {
                return true;
            }

            throw $this->exception($key, sprintf('must strict equal [%s], current value is [%s]', $rule['same'], $value));
        } elseif (isset($rule['eq'])) {
            if ($value == $rule['eq']) {
                return true;
            }

            throw $this->exception($key, sprintf('must equal [%s], current value is [%s]', $rule['eq'], $value));
        } elseif (isset($rule['enum_same'])) {
            if (in_array($value, $rule['enum_same'], true)) {
                return true;
            }

            throw $this->exception($key, sprintf('must be strict equal one of [%s], current value is "%s"', implode(', ', $rule['enum_same']), $value));
        } elseif (isset($rule['enum_eq'])) {
            if (in_array($value, $rule['enum_eq'])) {
                return true;
            }

            throw $this->exception($key, sprintf('must be equal one of [%s], current value is "%s"', implode(', ', $rule['enum_eq']), $value));
        } elseif ($regexp = $rule['regexp']) {
            if (!preg_match($regexp, $value)) {
                throw $this->exception($key, sprintf('mismatch regexp %s, current value is "%s"', $regexp, $value));
            }
        }

        if ($rule['type'] === 'boolean') {
            if (!is_bool($value)) {
                throw $this->exception($key, sprintf('must be TRUE or FALSE, current value is "%s"', $value));
            }
        } elseif ($rule['type'] === 'integer' || $rule['type'] === 'numeric') {
            if ($value < 0 && !$rule['allow_negative']) {
                throw $this->exception($key, sprintf('not allow negative numeric, current value is "%s"', $value));
            }

            if ($value == 0 && !$rule['allow_zero']) {
                throw $this->exception($key, sprintf('not allow zero, current value is "%s"', $value));
            }
        } elseif (!$rule['allow_tags'] && \Owl\str_has_tags($value)) {
            throw $this->exception($key, sprintf('content not allow tags, current value is "%s"', $value));
        }

        if ($callback = $rule['callback']) {
            if (!call_user_func_array($callback, [$value, $key, $rule])) {
                throw $this->exception($key, 'custom test failed');
            }
        }

        return true;
    }

    protected function checkArray($key, $value, array $rule)
    {
        if (!is_array($value)) {
            throw $this->exception($key, 'is not array type');
        }

        // 兼容老式写法
        if (isset($rule['element'])) {
            $rule['value'] = [
                'type' => 'array',
                'keys' => $rule['element'],
            ];

            unset($rule['element']);
        }

        if (!isset($rule['keys']) && !isset($rule['value'])) {
            throw $this->exception($key, 'rule missing "keys" or "value"');
        }

        if (isset($rule['keys']) && $rule['keys']) {
            $this->path[] = $key;

            $this->execute($value, $rule['keys']);

            array_pop($this->path);
        } elseif (isset($rule['value']) && $rule['value']) {
            $this->path[] = $key;

            foreach ($value as $k => $v) {
                $this->check($k, $v, $rule['value']);
            }

            array_pop($this->path);
        }

        return true;
    }

    protected function checkJson($key, $value, array $rule)
    {
        $value = json_decode($value, true);

        if ($value === null && ($error = json_last_error_msg())) {
            throw $this->exception($key, 'json_decode() failed, '.$error);
        }

        return $this->checkArray($key, $value, $rule);
    }

    protected function checkObject($key, $value, array $rule)
    {
        if (!is_object($value)) {
            throw $this->exception($key, 'is not object');
        }

        if (isset($rule['instanceof']) && !($value instanceof $rule['instanceof'])) {
            throw $this->exception($key, sprintf('must instanceof "%s"', $rule['instanceof']));
        }

        return true;
    }

    protected function normalizeRule(array $rule)
    {
        if (isset($rule['type'], self::$types[$rule['type']])) {
            $rule = array_merge(self::$types[$rule['type']], $rule);
        }

        $rule = array_merge([
            'type' => null,
            'required' => true,
            'allow_empty' => false,
            'allow_tags' => false,
            'regexp' => '',
            'callback' => null,
            '__normalized__' => true,
        ], $rule);

        switch ($rule['type']) {
            case 'bool':
                $rule['type'] = 'boolean';
                break;
            case 'hash':
                $rule['type'] = 'array';
                break;
            case 'int':
                $rule['type'] = 'integer';
                break;
            case 'float':
            case 'number':
                $rule['type'] = 'numeric';
                break;
            case 'text':
                $rule['type'] = 'string';
                break;
        }

        return $rule;
    }

    private function exception($key, $message)
    {
        $this->path[] = $key;
        $message = 'Key ['.implode('=>', $this->path).'], '.$message;

        $exception = new \Owl\Parameter\Exception($message);
        $exception->parameter = $key;

        return $exception;
    }

    public static function setType($type, array $rule)
    {
        static::$types[$type] = $rule;
    }
}
