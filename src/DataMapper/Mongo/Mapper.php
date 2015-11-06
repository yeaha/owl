<?php
namespace Owl\DataMapper\Mongo;

class Mapper extends \Owl\DataMapper\Mapper {
    public function pack(array $record, \Owl\DataMapper\Data $data = null) {
        if (isset($record['_id'])) {
            $record['_id'] = (string)$record['_id'];
        }

        return parent::pack($record, $data);
    }

    protected function doFind($id, \Owl\Service $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->findOne($collection, ['_id' => $this->normalizeID($id)]);
    }

    protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        $record = $this->unpack($data);
        $record['_id'] = $this->normalizeID($data->id());

        $service->insert($collection, $record);

        return [
            '_id' => $record['_id'],
        ];
    }

    protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data, ['dirty' => true]);

        return $service->update($collection, ['_id' => $this->normalizeID($data)], ['$set' => $record]);
    }

    protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null) {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->remove($collection, ['_id' => $this->normalizeID($data)]);
    }

    protected function normalizeOptions(array $options) {
        $options = parent::normalizeOptions($options);

        if (count($options['primary_key']) !== 1 || $options['primary_key'][0] !== '_id') {
            throw new \RuntimeException("Mongo data's primary key must be \"_id\"");
        }

        $options['attributes']['_id']['auto_generate'] = true;

        return $options;
    }

    protected function normalizeID($data) {
        $id = $data instanceof \Owl\DataMapper\Data
            ? $data->id()
            : $data;

        return $id instanceof \MongoId
             ? $id
             : new \MongoId($id);
    }
}
