<?php
namespace Owl\DataMapper\Type;

class Numeric extends \Owl\DataMapper\Type\Mixed {
    public function normalize($value, array $attribute) {
        return $value * 1;
    }
}
