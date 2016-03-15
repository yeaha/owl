<?php

namespace Owl\DataMapper\Type;

class Text extends Common
{
    public function normalize($value, array $attribute)
    {
        return $this->isNull($value) ? null : (string) $value;
    }
}
