<?php
namespace Owl\DataMapper\Type;

class String extends \Owl\DataMapper\Type\Mixed {
    public function normalize($value, array $attribute) {
        return (string)$value;
    }

    public function isNull($value) {
        return $value === null || $value === '';
    }
}
