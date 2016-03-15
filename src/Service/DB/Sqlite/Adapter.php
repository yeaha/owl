<?php

namespace Owl\Service\DB\Sqlite;

if (!extension_loaded('pdo_sqlite')) {
    throw new \Exception('Require "pdo_sqlite" extension.');
}

class Adapter extends \Owl\Service\DB\Adapter
{
    protected $identifier_symbol = '`';

    public function lastID($table = null, $column = null)
    {
        return $this->execute('SELECT last_insert_rowid()')->getCol();
    }

    public function getTables()
    {
    }

    public function getColumns($table)
    {
    }

    public function getIndexes($table)
    {
    }
}
