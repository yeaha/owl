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
 * $book->pushIn('doc', 'author', ['first_name' => 'F1', 'last_name' => 'L1']);
 * $book->pushIn('doc', 'author', ['first_name' => 'F2', 'last_name' => 'L2']);
 *
 * @see \Owl\Parameter\Checker
 */
class Complex extends Common {
    public function normalizeAttribute(array $attribute) {
        return array_merge([
            'schema' => [],
        ], $attribute);
    }

    public function store($value, array $attribute) {
        if ($value) {
            $value = self::trim($value);
        }

        return $this->isNull($value) ? null : $value;
    }

    public function restore($value, array $attribute) {
        return $this->isNull($value) ? [] : $value;
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
        if ($attribute['schema']) {
            $value = self::trim($value);

            (new \Owl\Parameter\Checker)->execute($value, $attribute['schema']);
        }
    }

    static public function setIn(array &$target, array $path, $value, $push = false) {
        $last_key = array_pop($path);

        foreach ($path as $key) {
            if (!array_key_exists($key, $target)) {
                $target[$key] = [];
            }

            $target = &$target[$key];

            if (!is_array($target)) {
                throw new \RuntimeException('Cannot use a scalar value as an array');
            }
        }

        if ($push) {
            if (!array_key_exists($last_key, $target)) {
                $target[$last_key] = [];
            } elseif (!is_array($target[$last_key])) {
                throw new \RuntimeException('Cannot use a scalar value as an array');
            }

            array_push($target[$last_key], $value);
        } else {
            $target[$last_key] = $value;
        }
    }

    /**
     * // set in
     * $target[$path] = $value;
     *
     * // push in
     * $target[$path][] = $value;
     */
    static public function pushIn(array &$target, array $path, $value) {
        static::setIn($target, $path, $value, true);
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

    /**
     * @example
     * $value = [
     *     'a' => [
     *         'b' => [],
     *     ],
     *     'c' => [
     *         'd' => [
     *             'e' => 1,
     *         ],
     *     ],
     * ];
     *
     * // [
     * //     'c' => [
     * //         'd' => [
     * //             'e' => 1,
     * //         ],
     * //     ],
     * // ];
     * $value = Complex::trim($value);
     */
    static public function trim(array $target) {
        $keys = array_keys($target);
        $is_array = ($keys === array_keys($keys));

        $result = [];

        foreach ($target as $key => $value) {
            if (is_array($value) && $value) {
                $value = self::trim($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $result[$key] = $value;
        }

        if ($is_array && $result) {
            $result = array_values($result);
        }

        return $result;
    }
}
