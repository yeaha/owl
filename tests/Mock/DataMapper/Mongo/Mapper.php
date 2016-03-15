<?php

namespace Tests\Mock\DataMapper\Mongo;

class Mapper extends \Owl\DataMapper\Mongo\Mapper
{
    public function setAttributes(array $attributes)
    {
        $options = $this->getOptions();
        $options['attributes'] = $attributes;

        $this->options = $this->normalizeOptions($options);
    }
}
