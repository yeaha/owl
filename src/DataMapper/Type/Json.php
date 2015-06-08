<?php
namespace Owl\DataMapper\Type;

class Json extends \Owl\DataMapper\Type\Mixed {
    public function normalize($value, array $attribute) {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        $value = json_decode($value, true);

        if ($value === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException(json_last_error_msg(), json_last_error());
        }

        return $value;
    }

    public function store($value, array $attribute) {
        if ($value === []) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function restore($value, array $attribute) {
        if ($value === null) {
            return [];
        }

        return $this->normalize($value, $attribute);
    }

    public function getDefaultValue(array $attribute) {
        return $attribute['default'] ?: [];
    }
}
