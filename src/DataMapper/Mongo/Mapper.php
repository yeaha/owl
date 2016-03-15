<?php

namespace Owl\DataMapper\Mongo;

class Mapper extends \Owl\DataMapper\Mapper
{
    public function query($expr)
    {
        $result = [];

        foreach (static::iterator($expr) as $data) {
            $result[$data->id()] = $data;
        }

        return $result;
    }

    public function iterator($expr = null)
    {
        if ($expr instanceof \MongoCursor) {
            $cursor = $expr;
        } elseif ($expr === null || is_array($expr)) {
            $cursor = $this->getService()
                           ->getCollection($this->getCollection())
                           ->find($expr ?: []);
        } else {
            throw new \InvalidArgumentException('Invalid mongo query expressions');
        }

        foreach ($cursor as $record) {
            $data = $this->pack($record);

            yield $data;
        }
    }

    public function pack(array $record, \Owl\DataMapper\Data $data = null)
    {
        if (isset($record['_id'])) {
            $record['_id'] = (string) $record['_id'];
        }

        return parent::pack($record, $data);
    }

    public function unpack(\Owl\DataMapper\Data $data, array $options = null)
    {
        $record = parent::unpack($data, $options);

        if ($data->isFresh()) {
            $record = \Owl\array_trim($record);
        }

        return $record;
    }

    protected function doFind($id, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->findOne($collection, ['_id' => $this->normalizeID($id)]);
    }

    protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        $record = $this->unpack($data);
        $record['_id'] = $this->normalizeID($data->id());

        $service->insert($collection, $record);

        return [
            '_id' => $record['_id'],
        ];
    }

    protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();
        $record = $this->unpack($data, ['dirty' => true]);

        $new = ['$set' => [], '$unset' => []];
        foreach ($record as $key => $value) {
            if ($value === null) {
                $new['$unset'][$key] = '';
            } else {
                $new['$set'][$key] = $value;
            }
        }

        if (!$new['$set']) {
            unset($new['$set']);
        }

        if (!$new['$unset']) {
            unset($new['$unset']);
        }

        return $service->update($collection, ['_id' => $this->normalizeID($data)], $new);
    }

    protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null)
    {
        $service = $service ?: $this->getService();
        $collection = $collection ?: $this->getCollection();

        return $service->remove($collection, ['_id' => $this->normalizeID($data)]);
    }

    protected function normalizeOptions(array $options)
    {
        $options = parent::normalizeOptions($options);

        if (count($options['primary_key']) !== 1 || $options['primary_key'][0] !== '_id') {
            throw new \RuntimeException("Mongo data's primary key must be \"_id\"");
        }

        $options['attributes']['_id']['auto_generate'] = true;

        return $options;
    }

    protected function normalizeID($data)
    {
        $id = $data instanceof \Owl\DataMapper\Data
            ? $data->id()
            : $data;

        return $id instanceof \MongoId
             ? $id
             : new \MongoId($id);
    }
}
