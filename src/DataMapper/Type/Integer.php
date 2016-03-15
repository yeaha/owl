<?php

namespace Owl\DataMapper\Type;

class Integer extends \Owl\DataMapper\Type\Number
{
    public function normalize($value, array $attribute)
    {
        return (int) $value;
    }
}
