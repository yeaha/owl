<?php

namespace Owl\DataMapper\Type;

/**
 * @example
 * class Book extends \Owl\DataMapper\Data {
 *     static protected $attributes = [
 *         'id' => ['type' => 'uuid', 'primary_key' => true],
 *         'doc' => [
 *             'type' => 'json',
 *             'schema' => [
 *                 'title' => ['type' => 'string'],
 *                 'description' => ['type' => 'string', 'required' => false, 'allow_empty' => true],
 *                 'author' => [
 *                     'type' => 'array',
 *                     'value' => [
 *                         'type' => 'array',
 *                         'keys' => [
 *                             'first_name' => ['type' => 'string'],
 *                             'last_name' => ['type' => 'string'],
 *                         ],
 *                     ],
 *                 ],
 *             ],
 *         ]
 *     ];
 * }
 *
 * $book = new Book;
 * $book->setIn('doc', 'title', 'book title');
 * $book->pushIn('doc', 'author', ['first_name' => 'F1', 'last_name' => 'L1']);
 * $book->pushIn('doc', 'author', ['first_name' => 'F2', 'last_name' => 'L2']);
 *
 * @see \Owl\Parameter\Validator
 */
class Complex extends Common
{
    public function normalizeAttribute(array $attribute)
    {
        return array_merge([
            'schema' => [],
        ], $attribute);
    }

    public function store($value, array $attribute)
    {
        if ($value) {
            $value = \Owl\array_trim($value);
        }

        return $this->isNull($value) ? null : $value;
    }

    public function restore($value, array $attribute)
    {
        return $this->isNull($value) ? [] : $value;
    }

    public function getDefaultValue(array $attribute)
    {
        return isset($attribute['default'])
             ? $attribute['default']
             : [];
    }

    public function isNull($value)
    {
        return $value === null || $value === '' || $value === [];
    }

    public function validateValue($value, array $attribute)
    {
        if ($attribute['schema']) {
            $value = \Owl\array_trim($value);

            (new \Owl\Parameter\Validator())->execute($value, $attribute['schema']);
        }
    }
}
