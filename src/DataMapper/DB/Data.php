<?php

namespace Owl\DataMapper\DB;

class Data extends \Owl\DataMapper\Data
{
    protected static $mapper = '\Owl\DataMapper\DB\Mapper';

    public static function select()
    {
        return static::getMapper()->select();
    }

    public static function getBySQL($sql, array $parameters = [], \Owl\Service $service = null)
    {
        $result = [];

        foreach (static::getBySQLAsIterator($sql, $parameters, $service) as $data) {
            $id = $data->id();

            if (is_array($id)) {
                $result[] = $data;
            } else {
                $result[$id] = $data;
            }
        }

        return $result;
    }

    public static function getBySQLAsIterator($sql, array $parameters = [], \Owl\Service $service = null)
    {
        return static::getMapper()->getBySQLAsIterator($sql, $parameters, $service);
    }
}
