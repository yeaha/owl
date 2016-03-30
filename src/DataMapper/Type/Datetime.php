<?php

namespace Owl\DataMapper\Type;

class Datetime extends Common
{
    public function normalize($value, array $attribute)
    {
        if ($this->isNull($value)) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        if (!isset($attribute['format'])) {
            return new \DateTime($value);
        }

        if (!$value = \DateTime::createFromFormat($attribute['format'], $value)) {
            throw new \UnexpectedValueException('Create datetime from format "'.$attribute['format'].'" failed!');
        }

        return $value;
    }

    public function store($value, array $attribute)
    {
        if ($value instanceof \DateTime) {
            $format = isset($attribute['format']) ? $attribute['format'] : 'c'; // ISO 8601
            $value = $value->format($format);
        }

        return $value;
    }

    public function getDefaultValue(array $attribute)
    {
        return ($attribute['default'] === null)
             ? null
             : new \DateTime($attribute['default']);
    }

    public function toJSON($value, array $attribute)
    {
        return $this->store($value, $attribute);
    }
}
