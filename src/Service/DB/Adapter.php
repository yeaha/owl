<?php

namespace Owl\Service\DB;

use Owl\Application as App;

abstract class Adapter extends \Owl\Service
{
    protected $handler;

    protected $identifier_symbol = '`';
    protected $support_savepoint = true;
    protected $savepoints = [];
    protected $in_transaction = false;

    abstract public function lastID($table = null, $column = null);

    /**
     * @return array
     */
    abstract public function getTables();

    /**
     * @param string $table
     *
     * @return [
     *           (string) => [
     *           'primary_key' => (boolean),
     *           'type' => (string),
     *           'sql_type' => (string),
     *           'character_max_length' => (integer),
     *           'numeric_precision' => (integer),
     *           'numeric_scale' => (integer),
     *           'default_value' => (mixed),
     *           'not_null' => (boolean),
     *           'comment' => (string),
     *           ],
     *           ...
     *           ]
     */
    abstract public function getColumns($table);

    /**
     * @param string $table
     *
     * @return [
     *           [
     *           'name' => (string),
     *           'columns' => (array),
     *           'is_primary' => (boolean),
     *           'is_unique' => (boolean),
     *           ],
     *           ...
     *           ]
     */
    abstract public function getIndexes($table);

    public function __construct(array $config = [])
    {
        if (!isset($config['dsn'])) {
            throw new \InvalidArgumentException('Invalid database config, require "dsn" key.');
        }
        parent::__construct($config);
    }

    public function __destruct()
    {
        if ($this->isConnected()) {
            $this->rollbackAll();
        }
    }

    public function __sleep()
    {
        $this->disconnect();
    }

    public function __call($method, array $args)
    {
        return $args
             ? call_user_func_array([$this->connect(), $method], $args)
             : $this->connect()->$method();
    }

    public function isConnected()
    {
        return $this->handler instanceof \PDO;
    }

    public function connect()
    {
        if ($this->isConnected()) {
            return $this->handler;
        }

        $dsn = $this->getConfig('dsn');
        $user = $this->getConfig('user') ?: null;
        $password = $this->getConfig('password') ?: null;
        $options = $this->getConfig('options') ?: [];

        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $options[\PDO::ATTR_STATEMENT_CLASS] = ['\Owl\Service\DB\Statement'];

        try {
            $handler = new \PDO($dsn, $user, $password, $options);

            App::log('debug', 'database connected', ['dsn' => $dsn]);
        } catch (\Exception $exception) {
            App::log('error', 'database connect failed', [
                'error' => $exception->getMessage(),
                'dsn' => $dsn,
            ]);

            throw new \Owl\Service\Exception('Database connect failed!', 0, $exception);
        }

        return $this->handler = $handler;
    }

    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->rollbackAll();
            $this->handler = null;

            App::log('debug', 'database disconnected', ['dsn' => $this->getConfig('dsn')]);
        }

        return $this;
    }

    public function begin()
    {
        if ($this->in_transaction) {
            if (!$this->support_savepoint) {
                throw new \Exception(get_class($this).' unsupport savepoint');
            }

            $savepoint = $this->quoteIdentifier(uniqid('savepoint_'));
            $this->execute('SAVEPOINT '.$savepoint);
            $this->savepoints[] = $savepoint;
        } else {
            $this->execute('BEGIN');
            $this->in_transaction = true;
        }

        return true;
    }

    public function commit()
    {
        if ($this->in_transaction) {
            if ($this->savepoints) {
                $savepoint = array_pop($this->savepoints);
                $this->execute('RELEASE SAVEPOINT '.$savepoint);
            } else {
                $this->execute('COMMIT');
                $this->in_transaction = false;
            }
        }

        return true;
    }

    public function rollback()
    {
        if ($this->in_transaction) {
            if ($this->savepoints) {
                $savepoint = array_pop($this->savepoints);
                $this->execute('ROLLBACK TO SAVEPOINT '.$savepoint);
            } else {
                $this->execute('ROLLBACK');
                $this->in_transaction = false;
            }
        }

        return true;
    }

    public function inTransaction()
    {
        return $this->in_transaction;
    }

    public function execute($sql, $params = null)
    {
        $params = $params === null
                ? []
                : is_array($params) ? $params : array_slice(func_get_args(), 1);

        App::log('debug', 'database execute', [
            'sql' => ($sql instanceof \PDOStatement) ? $sql->queryString : $sql,
            'parameters' => $params,
        ]);

        if ($sql instanceof \PDOStatement) {
            $sth = $sql;
            $sth->execute($params);
        } elseif ($params) {
            $sth = $this->connect()->prepare($sql);
            $sth->execute($params);
        } else {
            $sth = $this->connect()->query($sql);
        }

        $sth->setFetchMode(\PDO::FETCH_ASSOC);

        return $sth;
    }

    public function quote($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->quote($v);
            }

            return $value;
        }

        if ($value instanceof Expr) {
            return $value;
        }

        if ($value === null) {
            return 'NULL';
        }

        return $this->connect()->quote($value);
    }

    public function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return array_map([$this, 'quoteIdentifier'], $identifier);
        }

        if ($identifier instanceof Expr) {
            return $identifier;
        }

        $symbol = $this->identifier_symbol;
        $identifier = str_replace(['"', "'", ';', $symbol], '', $identifier);

        $result = [];
        foreach (explode('.', $identifier) as $s) {
            $result[] = $symbol.$s.$symbol;
        }

        return new Expr(implode('.', $result));
    }

    public function select($table)
    {
        return new \Owl\Service\DB\Select($this, $table);
    }

    public function insert($table, array $row)
    {
        $params = [];
        foreach ($row as $value) {
            if (!($value instanceof Expr)) {
                $params[] = $value;
            }
        }

        $sth = $this->prepareInsert($table, $row);

        return $this->execute($sth, $params)->rowCount();
    }

    public function update($table, array $row, $where = null, $params = null)
    {
        $where_params = ($where === null || $params === null)
                      ? []
                      : is_array($params) ? $params : array_slice(func_get_args(), 3);

        $params = [];
        foreach ($row as $value) {
            if (!($value instanceof Expr)) {
                $params[] = $value;
            }
        }

        if ($where_params) {
            $params = array_merge($params, $where_params);
        }

        $sth = $this->prepareUpdate($table, $row, $where);

        return $this->execute($sth, $params)->rowCount();
    }

    public function delete($table, $where = null, $params = null)
    {
        $params = ($where === null || $params === null)
                ? []
                : is_array($params) ? $params : array_slice(func_get_args(), 2);

        $sth = $this->prepareDelete($table, $where);

        return $this->execute($sth, $params)->rowCount();
    }

    public function prepareInsert($table, array $columns)
    {
        $values = array_values($columns);

        if ($values === $columns) {
            $values = array_fill(0, count($columns), '?');
        } else {
            $columns = array_keys($columns);

            foreach ($values as $key => $value) {
                if ($value instanceof Expr) {
                    continue;
                }
                $values[$key] = '?';
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(',', $this->quoteIdentifier($columns)),
            implode(',', $values)
        );

        return $this->prepare($sql);
    }

    public function prepareUpdate($table, array $columns, $where = null)
    {
        $only_column = (array_values($columns) === $columns);

        $set = [];
        foreach ($columns as $column => $value) {
            if ($only_column) {
                $set[] = $this->quoteIdentifier($value).' = ?';
            } else {
                $value = ($value instanceof Expr) ? $value : '?';
                $set[] = $this->quoteIdentifier($column).' = '.$value;
            }
        }

        $sql = sprintf('UPDATE %s SET %s', $this->quoteIdentifier($table), implode(',', $set));
        if ($where) {
            $sql .= ' WHERE '.$where;
        }

        return $this->prepare($sql);
    }

    public function prepareDelete($table, $where = null)
    {
        $table = $this->quoteIdentifier($table);

        $sql = sprintf('DELETE FROM %s', $table);
        if ($where) {
            $sql .= ' WHERE '.$where;
        }

        return $this->prepare($sql);
    }

    protected function rollbackAll()
    {
        $max = 9;   // 最多9次，避免死循环
        while ($this->in_transaction && $max-- > 0) {
            $this->rollback();
        }
    }
}
