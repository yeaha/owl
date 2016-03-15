<?php

namespace Owl\DataMapper\Type;

class UUID extends Common
{
    public function normalizeAttribute(array $attribute)
    {
        $attribute = array_merge([
            'upper' => false,
        ], $attribute);

        if (isset($attribute['primary_key']) && $attribute['primary_key']) {
            $attribute['auto_generate'] = true;
        }

        return $attribute;
    }

    public function getDefaultValue(array $attribute)
    {
        if (!$attribute['auto_generate']) {
            return $attribute['default'];
        }

        $uuid = self::generate();

        if (isset($attribute['upper']) && $attribute['upper']) {
            $uuid = strtoupper($uuid);
        }

        return $uuid;
    }

    // http://php.net/manual/en/function.uniqid.php#94959
    public static function generate()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        } else {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
            );
        }
    }
}
