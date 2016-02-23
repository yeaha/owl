<?php
namespace Owl\DataMapper\DB;

class Data extends \Owl\DataMapper\Data {
    static protected $mapper = '\Owl\DataMapper\DB\Mapper';

    static public function select() {
        return static::getMapper()->select();
    }

    static public function getBySQL($sql, array $parameters = [], \Owl\Service $service = null) {
        $result = [];

        foreach (static::getBySQLAsIterator($sql, $parameters, $service) as $data) {
            $id = $data->id();

            if (is_array($id)) {
                $result[] = $data;
            } else {
                $result[$data->id()] = $data;
            }
        }

        return $result;
    }

    static public function getBySQLAsIterator($sql, array $parameters = [], \Owl\Service $service = null) {
        return static::getMapper()->getBySQLAsIterator($sql, $parameters, $service);
    }
}
