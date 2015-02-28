<?php
namespace Tests\Mock\DataMapper;

class Service implements \Owl\Service {
    protected $data = array();

    public function __construct(array $config = array()) {
    }

    public function disconnect() {
    }

    public function find($table, $id) {
        $key = $this->keyOfId($id);

        if (!isset($this->data[$table][$key]))
            return false;

        return $this->data[$table][$key];
    }

    public function insert($table, array $row, $id = null) {
        if (!$id) {
            if (is_array($id)) {
                foreach ($id as $k => $v) {
                    if (!$v) $id[$k] = StorageSequence::getInstance()->next();
                }
            } else {
                $id = StorageSequence::getInstance()->next();
            }
        }

        $key = $this->keyOfId($id);

        $this->data[$table][$key] = $row;

        return $id;
    }

    public function update($table, array $row, $id) {
        if (!$this->find($table, $id))
            return false;

        $key = $this->keyOfId($id);
        $this->data[$table][$key] = array_merge($this->data[$table][$key], $row);

        return true;
    }

    public function delete($table, $id) {
        $key = $this->keyOfId($id);

        if (!isset($this->data[$table][$key]))
            return false;

        unset($this->data[$table][$key]);
        return true;
    }

    public function getLastId() {
        return StorageSequence::getInstance()->current();
    }

    public function clear($table = null) {
        if ($table) {
            $this->data[$table] = array();
        } else {
            $this->data = array();
        }
    }

    protected function keyOfId($id) {
        if (!is_array($id))
            return $id;

        ksort($id);
        return md5(strtolower(json_encode($id)));
    }
}

class StorageSequence {
    use \Owl\Traits\Singleton;

    protected $seq = 0;

    public function current() {
        return $this->seq;
    }

    public function next() {
        return ++$this->seq;
    }
}
