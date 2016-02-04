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

    public function getTables() {
        $select = $this->select('information_schema.tables')
                       ->setColumns('table_schema', 'table_name')
                       ->whereNotIn('table_schema', ['pg_catalog', 'information_schema']);

        $tables = [];
        foreach ($select->iterator() as $row) {
            $tables[] = sprintf('"%s"."%s"', $row['table_schema'], $row['table_name']);
        }

        return $tables;
    }

    public function getColumns($table) {
        $sql = <<< EOF
with primary_keys as (
    select
        c.column_name
    from
        information_schema.constraint_column_usage c,
        information_schema.table_constraints t
    where
        c.table_schema = ?
        and c.table_name = ?
        and c.table_schema = t.table_schema
        and c.table_name = t.table_name
        and c.constraint_name = t.constraint_name
        and t.constraint_type = 'PRIMARY KEY'
)
select
    c.table_schema,
    c.table_name,
    c.column_name,
    c.column_default,
    c.data_type,
    c.character_maximum_length,
    c.numeric_precision,
    c.numeric_scale,
    c.is_nullable,
    col_description(t.oid, c.ordinal_position) as comment,
    case when
        exists (select 1 from primary_keys where column_name = c.column_name)
        then 1
        else 0
        end as is_primary
from
    information_schema.columns c,
    pg_class t,
    pg_namespace s
where
    c.table_schema = ?
    and c.table_name = ?
    and c.table_schema = s.nspname
    and c.table_name = t.relname
    and t.relnamespace = s.oid
    and t.relkind = 'r'
order by
    c.ordinal_position
EOF;

        list($schema, $table) = $this->parseTableName($table);
        $res = $this->execute($sql, $schema, $table, $schema, $table);

        $columns = [];
        while ($row = $res->fetch()) {
            $name = $row['column_name'];

            $column = [
                'primary_key' => $row['is_primary'] === 1,
                'type' => $row['data_type'],
                'sql_type' => $row['data_type'],
                'character_max_length' => $row['character_maximum_length'] * 1,
                'numeric_precision' => $row['numeric_precision'] * 1,
                'numeric_scale' => $row['numeric_scale'] * 1,
                'default_value' => $row['column_default'],
                'not_null' => $row['is_nullable'] === 'NO',
                'comment' => (string)$row['comment'],
            ];

            $columns[$name] = $column;
        }

        return $columns;
    }

    public function getIndexes($table) {
        list($scheme, $table) = $this->parseTableName($table);

        $sql = <<< EOF
select
    s.nspname as scheme_name,
    t.relname as table_name,
    i.relname as index_name,
    a.attname as column_name,
    ix.indisprimary as is_primary,
    ix.indisunique as is_unique
from
    pg_namespace s,
    pg_class t,
    pg_class i,
    pg_index ix,
    pg_attribute a
where
    t.oid = ix.indrelid
    and s.oid = t.relnamespace
    and i.oid = ix.indexrelid
    and a.attrelid = t.oid
    and a.attnum = ANY(ix.indkey)
    and t.relkind = 'r'
    and s.nspname = ?
    and t.relname = ?
EOF;

        $indexes = [];
        $res = $this->execute($sql, $scheme, $table);
        while ($row = $res->fetch()) {
            $index_name = $row['index_name'];

            if (!isset($indexes[$index_name])) {
                $indexes[$index_name] = [
                    'name' => $index_name,
                    'columns' => [$row['column_name']],
                    'is_primary' => $row['is_primary'],
                    'is_unique' => $row['is_unique'],
                ];
            } else {
                $indexes[$index_name]['columns'][] = $row['column_name'];
            }
        }

        return array_values($indexes);
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
            return ['public', $table];
        }
    }
}
