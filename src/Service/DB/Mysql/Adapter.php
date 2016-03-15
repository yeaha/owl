<?php

namespace Owl\Service\DB\Mysql;

if (!extension_loaded('pdo_mysql')) {
    throw new \Exception('Require "pdo_mysql" extension.');
}

class Adapter extends \Owl\Service\DB\Adapter
{
    protected $identifier_symbol = '`';

    public function __construct(array $config = [])
    {
        if (isset($config['options'])) {
            $config['options'][\PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        } else {
            $config['options'] = [\PDO::MYSQL_ATTR_FOUND_ROWS => true];
        }

        parent::__construct($config);
    }

    public function lastID($table = null, $column = null)
    {
        return $this->execute('SELECT last_insert_id()')->getCol();
    }

    public function enableBufferedQuery()
    {
        $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return $this;
    }

    public function disableBufferedQuery()
    {
        $this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        return $this;
    }

    public function getTables()
    {
        return $this->select('information_schema.TABLES')
                    ->setColumns('TABLE_NAME')
                    ->where('TABLE_SCHEMA = database()')
                    ->execute()
                    ->getCols();
    }

    public function getColumns($table)
    {
        $select = $this->select('information_schema.COLUMNS')
                       ->where('TABLE_SCHEMA = database()')
                       ->where('TABLE_NAME = ?', $table)
                       ->orderBy('ORDINAL_POSITION');

        $columns = [];
        foreach ($select->iterator() as $row) {
            $name = $row['COLUMN_NAME'];

            $column = [
                'primary_key' => $row['COLUMN_KEY'] === 'PRI',
                'type' => $row['DATA_TYPE'],
                'sql_type' => $row['COLUMN_TYPE'],
                'character_max_length' => $row['CHARACTER_MAXIMUM_LENGTH'] * 1,
                'numeric_precision' => $row['NUMERIC_PRECISION'] * 1,
                'numeric_scale' => $row['NUMERIC_SCALE'] * 1,
                'default_value' => $row['COLUMN_DEFAULT'],
                'not_null' => $row['IS_NULLABLE'] === 'NO',
                'comment' => $row['COLUMN_COMMENT'],
                'charset' => $row['CHARACTER_SET_NAME'],
                'collation' => $row['COLLATION_NAME'],
            ];

            $columns[$name] = $column;
        }

        return $columns;
    }

    public function getIndexes($table)
    {
        $indexes = [];

        $sql = sprintf('show indexes from %s', $this->quoteIdentifier($table));
        $res = $this->execute($sql);

        while ($row = $res->fetch()) {
            $name = $row['Key_name'];

            if (!isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'columns' => [$row['Column_name']],
                    'is_primary' => $row['Key_name'] === 'PRIMARY',
                    'is_unique' => $row['Non_unique'] == 0,
                ];
            } else {
                $indexes[$name]['columns'][] = $row['Column_name'];
            }
        }

        return array_values($indexes);
    }
}
