<?php
namespace Owl\DataMapper\DB;

class Mapper extends \Owl\DataMapper\Mapper {
    public function select(\Owl\Service\IService $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $primary_key = $this->getPrimaryKey();

        // 只有一个主键，就可以返回以主键为key的数组结果
        if (count($primary_key) === 1) {
            $select = new \Owl\DataMapper\DB\Select($service, $collection);
        } else {
            $select = new \Owl\Service\DB\Select($service, $collection);
        }

        $select->setCols(array_keys($this->getAttributes()));

        $mapper = $this;
        $select->setProcessor(function($record) use ($mapper) {
            return $record ? $mapper->pack($record) : false;
        });

        return $select;
    }

    protected function doFind($id, \Owl\Service\IService $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        $select = $this->select($service, $collection);

        list($where, $params) = $this->whereID($service, $id);
        $select->where($where, $params);

        return $select->limit(1)->execute()->fetch();
    }

    protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service\IService $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data);

        if (!$service->insert($collection, $record)) {
            return false;
        }

        $id = [];
        foreach ($this->getPrimaryKey() as $key) {
            if (!isset($record[$key])) {
                if (!$last_id = $service->lastId($collection, $key)) {
                    throw new \Exception("{$this->class}: Insert record success, but get last-id failed!");
                }
                $id[$key] = $last_id;
            }
        }

        return $id;
    }

    protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service\IService $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data, ['dirty' => true]);

        list($where, $params) = $this->whereID($service, $data->id());

        return $service->update($collection, $record, $where, $params);
    }

    protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service\IService $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        list($where, $params) = $this->whereID($service, $data->id());

        return $service->delete($collection, $where, $params);
    }

    protected function whereID(\Owl\Service\IService $service, $id) {
        $primary_key = $this->getPrimaryKey();
        $key_count = count($primary_key);

        if ($key_count === 1 && !is_array($id)) {
            $key = $primary_key[0];
            $id = [$key => $id];
        }

        if (!is_array($id)) {
            throw new \Exception("{$this->class}: Illegal id value");
        }

        $where = $params = [];
        foreach ($primary_key as $key) {
            $where[] = $service->quoteIdentifier($key) .' = ?';

            if (!isset($id[$key])) {
                throw new \Exception("{$this->class}: Illegal id value");
            }

            $params[] = $id[$key];
        }
        $where = implode(' AND ', $where);

        return [$where, $params];
    }
}
