<?php
namespace Owl\DataMapper\Type;

class Json extends Complex {
    public function normalize($value, array $attribute) {
        if (is_array($value)) {
            return $value;
        }

        if ($this->isNull($value)) {
            return [];
        }

        $value = json_decode($value, true);

        if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $value;
    }

    public function store($value, array $attribute) {
        $value = parent::store($value, $attribute);

        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
    }

    public function restore($value, array $attribute) {
        if ($this->isNull($value)) {
            return [];
        }

        return $this->normalize($value, $attribute);
    }
}
