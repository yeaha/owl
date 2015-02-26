<?php
namespace Owl\Service\DB\Pgsql;

if (!extension_loaded('pdo_pgsql')) {
    throw new \Exception('Require "pdo_pgsql" extension!');
}

class Adapter extends \Owl\Service\DB\Adapter {
    protected $identifier_symbol = '"';

    public function lastID($table = null, $column = null) {
        $sql = ($table && $column)
             ? sprintf("SELECT CURRVAL('%s')", $this->sequenceName($table, $column))
             : 'SELECT LASTVAL()';

        return $this->execute($sql)->getCol();
    }

    public function nextID($table, $column) {
        $sql = sprintf("SELECT NEXTVAL('%s')", $this->sequenceName($table, $column));
        return $this->execute($sql)->getCol();
    }

    protected function sequenceName($table, $column) {
        list($schema, $table) = $this->parseTableName($table);

        $sequence = sprintf('%s_%s_seq', $table, $column);
        if ($schema) {
            $sequence = $schema .'.'. $sequence;
        }

        return $this->quoteIdentifier($sequence);
    }

    protected function parseTableName($table) {
        $table = str_replace('"', '', $table);
        $pos = strpos($table, '.');

        if ($pos) {
            list($schema, $table) = explode('.', $table, 2);
            return [$schema, $table];
        } else {
            return [null, $table];
        }
    }
}
