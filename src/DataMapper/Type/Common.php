<?php

namespace Owl\DataMapper\Type;

class Common
{
    /**
     * 格式化属性定义.
     *
     * @param array $attribute
     *
     * @return array
     */
    public function normalizeAttribute(array $attribute)
    {
        return $attribute;
    }

    /**
     * 格式化属性值
     *
     * @see \Lysine\DataMapper\Data::set()
     *
     * @param mixed $value
     * @param array $attribute
     *
     * @return mixed
     */
    public function normalize($value, array $attribute)
    {
        return $value;
    }

    /**
     * 把值转换为存储格式.
     *
     * @param mixed $value
     * @param array $attribute
     *
     * @return mixed
     */
    public function store($value, array $attribute)
    {
        return $this->isNull($value) ? null : $value;
    }

    /**
     * 把存储格式的值转换为属性值
     *
     * @param mixed $value
     * @param array $attribute
     *
     * @return mixed
     */
    public function restore($value, array $attribute)
    {
        return $this->isNull($value)
             ? null
             : $this->normalize($value, $attribute);
    }

    /**
     * 获取默认值
     *
     * @param array $attribute
     *
     * @return mixed
     */
    public function getDefaultValue(array $attribute)
    {
        return $attribute['default'];
    }

    /**
     * 转换为对json_encode友好的格式.
     *
     * @param mixed $value
     * @param array $attribute
     *
     * @return mixed
     */
    public function toJSON($value, array $attribute)
    {
        return $value;
    }

    /**
     * 判断值是否空值
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isNull($value)
    {
        return $value === null || $value === '';
    }

    /**
     * 自定义检查逻辑.
     *
     * @param mixed $value
     * @param array $attribute
     */
    public function validateValue($value, array $attribute)
    {
    }

    /**
     * 获得clone值
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function cloneValue($value)
    {
        return is_object($value) ? clone $value : $value;
    }
}
