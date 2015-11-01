<?php
namespace Owl\DataMapper\Type;

abstract class Complex extends Mixed {
    public function normalizeAttribute(array $attribute) {
        return array_merge([
            'rules' => [],
        ], $attribute);
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
