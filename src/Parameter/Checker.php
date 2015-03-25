<?php
namespace Owl\Parameter;

/**
 * 参数有效性检查，检查参数是否存在，值是否符合要求
 *
 * @example
 *
 * $checker = new \Owl\Parameter\Checker;
 * $checker->execute($parameters, array(
 *     'foo' => [
 *         'type' => 'integer',                 // 数据格式类型
 *         'allow_empty' => true,               // 是否允许空字符串
 *         'required' => false,                 // 是否允许不传值
 *         'eq' => '0',                         // "==="检查
 *         'enum' => ['0', '1', '2'],           // 枚举值检查
 *         'regexp' => '/^\d+$/',               // 自定义正则表达式检查
 *     ],
 *     'bar' => [],                             // 不指定任何配置就用默认配置
 *     'foobar' => [
 *         'type' => 'hash',                    // 字典类型
 *         'allow_empty' => true,               // 是否允许空hash数组
 *         'keys' => [                          // 声明字典值配置
 *             'foo' => [                       // 字典和数组都可以任意嵌套
 *                 'type' => 'array',           // 数组类型
 *                 'allow_empty' => true,       // 是否允许空数组
 *                 'element' => [               // 数组每个元素的值配置
 *                     'bar' => [],
 *                 ],
 *             ],
 *         ],
 *     ],
 *     'baz' => [
 *         'type' => 'object',                  // 对象类型检查
 *         'instanceof' => '\Baz'               // 对象所属类检查
 *     ],
 *     'x' => [
 *         'type' => 'json',
 *         'keys' => [
 *             // ...
 *         ],
 *     ],
 *     'y' => [
 *         'type' => 'json',
 *         'element' => [
 *             // ...
 *         ],
 *     ],
 * ));
 */


class Checker {
    protected $type_options = [
        'integer' => [
            'regexp' => '/^\d+$/',
        ],
        'numeric' => [
            'regexp' => '/^\d+(?:\.\d+)?$/',
        ],
        'telphone' => [
            'regexp' => '/^1[34578]\d{9}$/',
        ],
        'url' => [
            'regexp' => '#^[a-z]+://[0-9a-z\-\.]+\.[0-9a-z]{1,4}(?:\d+)?(?:/[^\?]*)?(?:\?[^\#]*)?(?:\#[0-9a-z\-\_\/]*)?$#',
        ],
        'uri' => [
            'regexp' => '#^/(?:[^?]*)?(?:\?[^\#]*)?(?:\#[0-9a-z\-\_\/]*)?$#',
        ],
        'ip' => [
            'regexp' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',
        ],
    ];

    protected $path;

    /**
     * 检查参数内容是否符合配置要求
     *
     * @param array $values
     * @param array $options
     * @param array $path
     * @return true
     */
    public function execute(array $values, array $options, array $path = []) {
        $this->path = $path;

        foreach ($options as $key => $option) {
            $option = $this->normalizeOption($option);

            if (!isset($values[$key])) {
                if ($option['required']) {
                    throw $this->exception($key, sprintf('Require "%s" parameter', $key));
                }

                continue;
            }

            $this->check($key, $values[$key], $option);
        }

        return true;
    }

    protected function check($key, $value, array $option) {
        switch ($option['type']) {
        case 'hash':
            return $this->checkHash($key, $value, $option);
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

    /**
     * 检查字面量类型，如字符串、数字
     *
     * @param string $key
     * @param mixed $value
     * @param array $option
     * @return void
     */
    protected function checkLiteral($key, $value, array $option) {
        if ($value === '') {
            if ($option['allow_empty']) {
                return;
            }

            throw $this->exception($key, 'value not allow empty string');
        }

        if (isset($option['eq'])) {
            if ($value !== $option['eq']) {
                throw $this->exception($key, sprintf('value must equal "%s", current value is "%s"', $option['eq'], $value));
            }
        } elseif ($option['enum']) {
            if (!in_array($value, $option['enum'], true)) {
                throw $this->exception($key, sprintf('value must be one of [%s], current value is "%s"', implode(', ', $option['enum']), $value));
            }
        } elseif ($option['regexp']) {
            if (!preg_match($option['regexp'], $value)) {
                throw $this->exception($key, sprintf('value mismatch regexp %s, current value is "%s"', $option['regexp'], $value));
            }
        }
    }

    /**
     * 检查字典类型
     *
     * @param string $key
     * @param mixed $value
     * @param array $option
     * @return void
     */
    protected function checkHash($key, $value, array $option) {
        if (!is_array($value) || ($value && array_values($value) === $value)) {
            throw $this->exception($key, 'value is not hash type');
        }

        if (!$value) {
            if (!$option['allow_empty']) {
                throw $this->exception($key, 'value not allow empty hash');
            }

            return;
        }

        if (isset($option['keys']) && $option['keys']) {
            $this->path[] = $key;
            $this->execute($value, $option['keys'], $this->path);
        }
    }

    /**
     * 检查数组类型
     *
     * @param string $key
     * @param mixed $value
     * @param array $option
     * @return void
     */
    protected function checkArray($key, $value, array $option) {
        if (!is_array($value) || ($value && array_values($value) !== $value)) {
            throw $this->exception($key, 'value is not array type');
        }

        if (!$value) {
            if (!$option['allow_empty']) {
                throw $this->exception($key, 'value not allow empty array');
            }

            return;
        }

        if (isset($option['element']) && $option['element']) {
            $this->path[] = $key;
            foreach ($value as $element) {
                $this->execute($element, $option['element'], $this->path);
            }
        }
    }

    /**
     * 检查对象类型
     *
     * @param string $key
     * @param mixed $value
     * @param array $option
     * @return void
     */
    protected function checkObject($key, $value, array $option) {
        if (!is_object($value)) {
            $this->exception($key, 'value is not object');
        }

        if (isset($option['instanceof']) && !($value instanceof $option['instanceof'])) {
            $this->exception($key, sprintf('value must instanceof "%s"', $option['instanceof']));
        }
    }

    /**
     * 检查json数组内容
     *
     * @param string $key
     * @param mixed $value
     * @param array $option
     * @return void
     */
    protected function checkJson($key, $value, array $option) {
        if ($value === '') {
            if ($option['allow_empty']) {
                return;
            }

            throw $this->exception($key, 'value not allow empty string');
        }

        $value = json_decode($value, true);

        if ($value === null && ($error = json_last_error_msg())) {
            throw $this->exception($key, 'json_decode() failed, '. $error);
        }

        if (isset($option['keys']) && $option['keys']) {
            $this->path[] = $key;
            $this->execute($value, $option['keys'], $this->path);
        } elseif (isset($option['element']) && $option['element']) {
            $this->path[] = $key;
            foreach ($value as $element) {
                $this->execute($element, $option['element'], $this->path);
            }
        }
    }

    /**
     * 格式化并补全配置
     *
     * @param array $option
     * @return array
     */
    private function normalizeOption(array $option) {
        if (isset($option['type']) && isset($this->type_options[$option['type']])) {
            $option = array_merge($this->type_options[$option['type']], $option);
        }

        // 默认配置补全
        $option = array_merge([
            'type' => 'string',
            'required' => true,     // 是否允许不传
            'allow_empty' => false, // 允许空字符串
            'regexp' => '',         // 正则检查
            'enum' => [],           // 枚举内容检查
        ], $option);

        if (isset($option['eq'])) {
            $option['eq'] = (string)$option['eq'];
        }

        if ($option['enum']) {
            $enum = array();
            foreach ($option['enum'] as $value) {
                $enum[] = (string)$value;
            }
            $option['enum'] = $enum;
        }

        return $option;
    }

    protected function exception($parameter, $message) {
        $this->path[] = $parameter;
        $message = 'Parameter ['.implode('->', $this->path).'], '.$message;

        $exception = new \Owl\Parameter\Exception($message);
        $exception->parameter = $parameter;

        return $exception;
    }
}
