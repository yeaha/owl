<?php
namespace Owl\DataMapper\Type;

class Integer extends \Owl\DataMapper\Type\Numeric {
    public function normalize($value, array $attribute) {
        return (int)$value;
    }
}
