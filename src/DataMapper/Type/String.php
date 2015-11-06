<?php
namespace Owl\DataMapper\Type;

class String extends \Owl\DataMapper\Type\Mixed {
    public function normalizeAttribute(array $attribute) {
        return array_merge([
            'allow_tags' => false,
        ], $attribute);
    }

    public function normalize($value, array $attribute) {
        return $this->isNull($value) ? null : (string)$value;
    }

    public function validateValue($value, array $attribute) {
        if (!$attribute['allow_tags'] && (strip_tags($value) != $value)) {
            throw new \UnexpectedValueException('cannot contain tags');
        }
    }
}
