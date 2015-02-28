<?php
namespace Owl\Service\DB\Mysql;

if (!extension_loaded('pdo_mysql')) {
    throw new \Exception('Require "pdo_mysql" extension.');
}

class Adapter extends \Owl\Service\DB\Adapter {
    protected $identifier_symbol = '`';

    public function __construct(array $config = []) {
        if (isset($config['options'])) {
            $config['options'][\PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        } else {
            $config['options'] = [\PDO::MYSQL_ATTR_FOUND_ROWS => true];
        }

        parent::__construct($config);
    }

    public function lastID($table = null, $column = null) {
        return $this->execute('SELECT last_insert_id()')->getCol();
    }

    public function enableBufferedQuery() {
        $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        return $this;
    }

    public function disableBufferedQuery() {
        $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        return $this;
    }
}
