<?php
namespace Owl\DataMapper\DB;

/**
 * @example
 * use \Owl\DataMapper\DB\CacheMapper;
 *
 * class MyMapper extends CacheMapper {
 *     protected function getCache($id) {
 *         // ...
 *     }
 *
 *     protected function deleteCache($id) {
 *         // ...
 *     }
 *
 *     protected function saveCache($id, array $record) {
 *         // ...
 *     }
 * }
 *
 * class MyData extends \Owl\DataMapper\Data {
 *     static protected $mapper = 'MyMapper';
 *
 *     static protected $mapper_options = [
 *         'service' => 'my.db',
 *         'collection' => 'tablename',
 *         'cache_policy' => CacheMapper::CACHE_INSERT | CacheMapper::CACHE_UPDATE, // require php-5.6
 *     ];
 *
 *     static protected $attributes = [
 *         // ...
 *     ];
 * }
 */
abstract class CacheMapper extends \Owl\DataMapper\DB\Mapper {
    const CACHE_NONE = 0;           // disable cache functions
    const CACHE_FIND = 1;           // create cache after found, default enable
    const CACHE_INSERT = 2;         // create cache after insert
    const CACHE_UPDATE = 4;         // update cahce after update

    /**
     * @param mixed $id
     * @return array
     */
    abstract protected function getCache($id);

    /**
     * @param mixed $id
     * @return boolean
     */
    abstract protected function deleteCache($id);

    /**
     * @param mixed $id
     * @param array $record
     * @return boolean
     */
    abstract protected function saveCache($id, array $record);

    protected function __afterInsert(\Owl\DataMapper\Data $data) {
        if (!$policy = $this->getCachePolicy()) {
            return;
        }

        $cache_insert = ($policy & self::CACHE_INSERT) === self::CACHE_INSERT;
        if ($cache_insert) {
            $id = $data->id();
            $record = $this->unpack($data);

            $this->saveCache($id, $record);
        }
    }

    protected function __afterUpdate(\Owl\DataMapper\Data $data) {
        if (!$policy = $this->getCachePolicy()) {
            return;
        }

        $cache_update = ($this->getCachePolicy() & self::CACHE_UPDATE) === self::CACHE_UPDATE;
        if ($cache_update) {
            $id = $data->id();
            $record = $this->unpack($data);

            $this->saveCache($id, $record);
        } else {
            $this->deleteCache($data->id());
        }
    }

    protected function __afterDelete(\Owl\DataMapper\Data $data) {
        if ($this->getCachePolicy()) {
            $this->deleteCache($data->id());
        }
    }

    public function refresh(\Owl\DataMapper\Data $data) {
        if ($this->getCachePolicy()) {
            $this->deleteCache($data->id());
        }

        return parent::refresh($data);
    }

    /**
     * 获得缓存策略配置
     *
     * @return integer
     */
    protected function getCachePolicy() {
        if ($this->hasOption('cache_policy')) {
            return $this->getOption('cache_policy');
        }

        return self::CACHE_FIND;
    }

    protected function doFind($id, \Owl\Service $service = null, $collection = null) {
        if (!$this->getCachePolicy()) {
            return parent::doFind($id, $service, $collection);
        }

        if ($record = $this->getCache($id)) {
            return $record;
        }

        if (!$record = parent::doFind($id, $service, $collection)) {
            return $record;
        }

        // 值为NULL的字段不用缓存
        foreach ($record as $key => $val) {
            if ($val === null) {
                unset($record[$key]);
            }
        }

        $this->saveCache($id, $record);

        return $record;
    }
}
