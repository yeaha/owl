<?php
namespace Owl\DataMapper\DB;

abstract class CacheMapper extends \Owl\DataMapper\DB\Mapper {
    abstract protected function getCache($id);
    abstract protected function deleteCache($id);
    abstract protected function saveCache($id, array $record);

    protected function __afterUpdate(\Owl\DataMapper\Data $data) {
        $this->deleteCache($data->id());
    }

    protected function __afterDelete(\Owl\DataMapper\Data $data) {
        $this->deleteCache($data->id());
    }

    public function refresh(\Owl\DataMapper\Data $data) {
        $this->deleteCache($data->id());
        return parent::refresh($data);
    }

    protected function doFind($id, \Owl\Service $service = null, $collection = null) {
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
