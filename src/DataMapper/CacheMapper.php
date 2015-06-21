<?php
namespace Owl\DataMapper;

const CACHE_NONE = 0;           // disable cache functions
const CACHE_FIND = 1;           // create cache after found, default enable
const CACHE_INSERT = 2;         // create cache after insert
const CACHE_UPDATE = 4;         // update cahce after update

/**
 * @example
 * class MyMapper extends \Owl\DataMapper\DB\Mapper {
 *     use \Owl\DataMapper\CacheMapper;
 *
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
 *         'cache_policy' => \Owl\DataMapper\CACHE_INSERT | \Owl\DataMapper\CACHE_UPDATE, // require php-5.6
 *     ];
 *
 *     static protected $attributes = [
 *         // ...
 *     ];
 * }
 */
trait CacheMapper {
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

    /**
     * create cache after save new data, if cache policy set
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterInsert(\Owl\DataMapper\Data $data) {
        if ($policy = $this->getCachePolicy()) {
            $cache_insert = ($policy & \Owl\DataMapper\CACHE_INSERT) === \Owl\DataMapper\CACHE_INSERT;

            if ($cache_insert) {
                $id = $data->id();
                $record = $this->unpack($data);
                $record = $this->normalizeCacheRecord($record);

                $this->saveCache($id, $record);
            }
        }

        parent::__afterInsert($data);
    }

    /**
     * delete or update cache after save data
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterUpdate(\Owl\DataMapper\Data $data) {
        if ($policy = $this->getCachePolicy()) {
            $cache_update = ($this->getCachePolicy() & \Owl\DataMapper\CACHE_UPDATE) === \Owl\DataMapper\CACHE_UPDATE;

            if ($cache_update) {
                $id = $data->id();
                $record = $this->unpack($data);
                $record = $this->normalizeCacheRecord($record);

                $this->saveCache($id, $record);
            } else {
                $this->deleteCache($data->id());
            }
        }

        parent::__afterUpdate($data);
    }

    /**
     * delete cache after delete data
     *
     * @param \Owl\DataMapper\Data $data
     * @return void
     */
    protected function __afterDelete(\Owl\DataMapper\Data $data) {
        if ($this->getCachePolicy()) {
            $this->deleteCache($data->id());
        }

        parent::__afterDelete($data);
    }

    /**
     * delete cache before refresh data
     *
     * @param \Owl\DataMapper\Data $data
     * @return \Owl\DataMapper\Data
     */
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

        return \Owl\DataMapper\CACHE_FIND;
    }

    /**
     * return record from cache if cache is created, or save data into cache
     *
     * @param mixed $id
     * @param \Owl\Service $service
     * @param string $collection
     * @return array
     */
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

        $record = $this->normalizeCacheRecord($record);
        $this->saveCache($id, $record);

        return $record;
    }

    /**
     * remove NULL value from record
     *
     * @param array $record
     * @return array
     */
    protected function normalizeCacheRecord(array $record) {
        // 值为NULL的字段不用缓存
        foreach ($record as $key => $val) {
            if ($val === null) {
                unset($record[$key]);
            }
        }

        return $record;
    }
}
