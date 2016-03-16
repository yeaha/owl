<?php

namespace Owl\DataMapper;

class Type
{
    /**
     * 数据类型helper实例缓存.
     *
     * @var array
     */
    protected $types = [];

    /**
     * 数据类型对应类名数组.
     *
     * @var array
     */
    protected $type_classes = [];

    /**
     * 根据数据类型名字获得对应的数据类型helper.
     *
     * @param string $type
     *
     * @return object 数据类型helper实例
     */
    public function get($type)
    {
        $type = strtolower($type);

        if ($type == 'int') {
            $type = 'integer';
        } elseif ($type == 'text') {
            $type = 'string';
        } elseif ($type == 'numeric') {
            $type = 'number';
        }

        if (!isset($this->type_classes[$type])) {
            $type = 'common';
        }

        if (isset($this->types[$type])) {
            return $this->types[$type];
        }

        $class = $this->type_classes[$type];

        return $this->types[$type] = new $class();
    }

    /**
     * 注册一个新的数据类型helper.
     *
     * @param string $type  数据类型名字
     * @param string $class helper类名
     *
     * @return $this
     */
    public function register($type, $class)
    {
        $type = strtolower($type);
        $this->type_classes[$type] = $class;

        return $this;
    }

    /**
     * 工厂方法.
     *
     * @param string $name
     *
     * @return object
     */
    public static function factory($name)
    {
        return static::getInstance()->get($name);
    }

    /**
     * 格式化并补全属性定义数组.
     *
     * @param array $attribute
     *
     * @return array
     */
    public static function normalizeAttribute(array $attribute)
    {
        $defaults = [
            // 是否允许为空
            'allow_null' => false,

            // 安全特性
            // 是否允许内容包含html/xml tag
            'allow_tags' => false,

            // 是否自动生成属性值，例如mysql里面的auto increase
            'auto_generate' => false,

            // 默认值
            'default' => null,

            // 标记为“废弃”属性
            'deprecated' => false,

            // 正则表达式检查
            'regexp' => null,

            // 是否主键
            'primary_key' => false,

            // 安全特性
            // 标记为protected的属性会在输出时被自动忽略
            // 避免不小心把敏感数据泄漏到客户端
            'protected' => false,

            // 保存之后不允许修改
            'refuse_update' => false,

            // 安全特性
            // 标记为strict的属性只能在严格开关被打开的情况下才能够赋值
            // 避免不小心被误修改到
            'strict' => null,

            // 数据类型
            'type' => null,
        ];

        $type = isset($attribute['type']) ? $attribute['type'] : null;

        if (isset($attribute['pattern'])) {
            $attribute['regexp'] = $attribute['pattern'];
            unset($attribute['pattern']);
        }

        $attribute = array_merge(
            $defaults,
            self::factory($type)->normalizeAttribute($attribute)
        );

        if ($attribute['allow_null']) {
            $attribute['default'] = null;
        }

        if ($attribute['primary_key']) {
            $attribute['allow_null'] = false;
            $attribute['refuse_update'] = true;
            $attribute['strict'] = true;
        }

        if ($attribute['protected'] && $attribute['strict'] === null) {
            $attribute['strict'] = true;
        }

        return $attribute;
    }

    private static $instance;

    public static function getInstance()
    {
        return self::$instance ?: (self::$instance = new self());
    }
}

Type::getInstance()
    ->register('common', '\Owl\DataMapper\Type\Common')
    ->register('datetime', '\Owl\DataMapper\Type\Datetime')
    ->register('integer', '\Owl\DataMapper\Type\Integer')
    ->register('json', '\Owl\DataMapper\Type\Json')
    ->register('number', '\Owl\DataMapper\Type\Number')
    ->register('pg_array', '\Owl\DataMapper\Type\PgsqlArray')
    ->register('pg_hstore', '\Owl\DataMapper\Type\PgsqlHstore')
    ->register('string', '\Owl\DataMapper\Type\Text')
    ->register('uuid', '\Owl\DataMapper\Type\UUID')
    ->register('complex', '\Owl\DataMapper\Type\Complex');
