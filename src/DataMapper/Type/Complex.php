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
 *                     'element' => [
 *                         'first_name' => ['type' => 'string'],
 *                         'last_name' => ['type' => 'string'],
 *                     ],
 *                 ],
 *             ],
 *         ]
 *     ];
 * }
 *
 * $book = new Book;
 * $book->setIn('doc', 'title', 'book title');
 * $book->setIn('doc', 'author', [{'first_name' => 'foo', 'last_name' => 'bar'}]);
 *
 * @see \Owl\Parameter\Checker
 */
abstract class Complex extends Mixed {
    public function normalizeAttribute(array $attribute) {
        return array_merge([
            'schema' => [],
        ], $attribute);
    }

    public function getDefaultValue(array $attribute) {
        return isset($attribute['default'])
             ? $attribute['default']
             : [];
    }

    public function isNull($value) {
        return $value === null || $value === '' || $value === [];
    }

    public function validateValue($value, array $attribute) {
        if (!$this->isNull($value) && $attribute['schema']) {
            (new \Owl\Parameter\Checker)->execute($value, $attribute['schema']);
        }
    }

    static public function setIn(array &$target, array $path, $value) {
        $last_key = array_pop($path);

        foreach ($path as $key) {
            if (!array_key_exists($key, $target) || !is_array($target[$key])) {
                $target[$key] = [];
            }

            $target = &$target[$key];
        }

        $target[$last_key] = $value;
    }

    static public function getIn(array $target, array $path) {
        foreach ($path as $key) {
            if (!isset($target[$key])) {
                return false;
            }

            $target = &$target[$key];
        }

        return $target;
    }

    static public function unsetIn(array &$target, array $path) {
        $last_key = array_pop($path);

        foreach ($path as $key) {
            if (!is_array($target)) {
                return;
            }

            if (!array_key_exists($key, $target)) {
                return;
            }

            $target = &$target[$key];
        }

        unset($target[$last_key]);
    }
}
