<?php
namespace Owl\DataMapper\Type;

class Text extends \Owl\DataMapper\Type\Mixed {
    public function normalize($value, array $attribute) {
        return $this->isNull($value) ? null : (string)$value;
    }
}
