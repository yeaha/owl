<?php

namespace Owl\DataMapper\Type;

class Number extends Common
{
    public function normalize($value, array $attribute)
    {
        return $value * 1;
    }
}
