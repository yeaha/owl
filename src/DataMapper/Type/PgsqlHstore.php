<?php
namespace Owl\DataMapper\Type;

use \Owl\Service\DB\Expr;

class PgsqlHstore extends Complex {
    public function normalize($value, array $attribute) {
        if ($this->isNull($value)) {
            return [];
        }

        if (!is_array($value)) {
            throw new \UnexpectedValueException('Postgresql hstore must be of the type array');
        }

        return $value;
    }

    public function store($value, array $attribute) {
        if ($this->isNull($value)) {
            return null;
        }

        return self::encode($value);
    }

    public function restore($value, array $attribute) {
        if ($this->isNull($value)) {
            return [];
        }

        return self::decode($value);
    }

    static public function encode($array) {
        if (!$array) {
            return NULL;
        }

        if (!is_array($array)) {
            return $array;
        }

        $expr = [];

        foreach ($array as $key => $val) {
            $search = ['\\', "'", '"'];
            $replace = ['\\\\', "''", '\"'];

            $key = str_replace($search, $replace, $key);

            if ($val === NULL) {
                $val = 'NULL';
            } else {
                $val = rtrim($val, '\\');       // 以\结尾的字符串，无法用正则表达式解析
                $val = '"'.str_replace($search, $replace, $val).'"';
            }

            $expr[] = sprintf('"%s"=>%s', $key, $val);
        }

        return new Expr(sprintf("'%s'::hstore", implode(',', $expr)));
    }

    static public function decode($hstore) {
        if (!$hstore || !preg_match_all('/"(.+)(?<!\\\)"=>(NULL|""|".+(?<!\\\)"),?/Us', $hstore, $match, PREG_SET_ORDER)) {
            return [];
        }

        $array = [];

        foreach ($match as $set) {
            list(, $k, $v) = $set;

            $v = $v === 'NULL'
               ? NULL
               : substr($v, 1, -1);

            $search = ['\"', '\\\\'];
            $replace = ['"', '\\'];

            $k = str_replace($search, $replace, $k);
            if ($v !== NULL) {
                $v = str_replace($search, $replace, $v);
            }

            $array[$k] = $v;
        }

        return $array;
    }
}
