<?php
namespace Owl\Service\DB;

use Owl\Service\DB\Adapter;
use Owl\Service\DB\Expr;

class Select {
    /**
     * 数据库连接
     * @var $adapter
     */
    protected $adapter;

    /**
     * 被查询的表或关系
     * @var $table
     */
    protected $table;

    /**
     * 查询条件表达式
     * @var $where
     */
    protected $where = [];

    /**
     * 查询结果字段
     * @var array
     */
    protected $columns = [];

    /**
     * group by 语句
     * @var array
     */
    protected $group_by;

    /**
     * order by 语句
     * @var array
     */
    protected $order_by;

    /**
     * limit 语句参数
     * @var integer
     */
    protected $limit = 0;

    /**
     * offset 语句参数
     * @var integer
     */
    protected $offset = 0;

    /**
     * 预处理函数
     * 每条返回的结果都会被预处理函数处理一次
     *
     * @see Select::get()
     * @var Callable
     */
    protected $processor;

    /**
     * @param \Owl\Service\DB\Adapter $adapter
     * @param string|Expr|Select $table
     */
    public function __construct(Adapter $adapter, $table) {
        $this->adapter = $adapter;
        $this->table = $table;
    }

    public function __destruct() {
        $this->adapter = null;
    }

    /**
     * 返回select语句
     *
     * @return string
     */
    public function __toString() {
        list($sql,) = $this->compile();
        return $sql;
    }

    /**
     * 获取数据库连接
     *
     * @return \Owl\Service\DB\Adapter
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * 设置查询的字段
     *
     * @param string|array $columns
     * @return $this
     *
     * @example
     * $select->setColumns('foo', 'bar');
     * $select->setColumns(array('foo', 'bar'));
     * $select->setColumns('foo', 'bar', new DB\Expr('foo + bar'));
     */
    public function setColumns($columns) {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * 设置查询条件
     * 通过where()方法设置的多条条件之间的关系都是AND
     * OR关系必须写到同一个where条件内
     *
     * @param string $where
     * @param mixed... [$params]
     * @return $this
     *
     * @example
     * $select->where('foo = ?', 1)->where('bar = ?', 2);
     * $select->where('foo = ? or bar = ?', 1, 2);
     */
    public function where($where, $params = null) {
        $params = $params === null
                ? []
                : is_array($params) ? $params : array_slice(func_get_args(), 1);

        $this->where[] = [$where, $params];
        return $this;
    }

    /**
     * in 子查询
     *
     * @param string $column
     * @param array|Select $relation
     * @return $this
     *
     * @example
     * // select * from foobar where foo in (1, 2, 3)
     * $select->whereIn('foo', array(1, 2, 3));
     *
     * // select * from foo where id in (select foo_id from bar where bar > 1)
     * $foo_select = $db->select('foo');
     * $bar_select = $db->select('bar');
     *
     * $foo_select->whereIn('id', $bar_select->setcolumns('foo_id')->where('bar > 1'));
     */
    public function whereIn($column, $relation) {
        return $this->whereSub($column, $relation, true);
    }

    /**
     * not in 子查询
     *
     * @param string $column
     * @param array|Select $relation
     * @return $this
     */
    public function whereNotIn($column, $relation) {
        return $this->whereSub($column, $relation, false);
    }

    /**
     * group by 条件
     *
     * @param array $columns
     * @param string [$having]
     * @param mixed... [$having_params]
     * @return $this
     *
     * @example
     * // select foo, count(1) from foobar group by foo having count(1) > 2
     * $select->setcolumns('foo', new Expr('count(1) as count'))->groupBy('foo', 'count(1) > ?', 2);
     */
    public function groupBy($columns, $having = null, $having_params = null) {
        $having_params = ($having === null || $having_params === null)
                       ? []
                       : is_array($having_params) ? $having_params : array_slice(func_get_args(), 2);

        $this->group_by = [$columns, $having, $having_params];
        return $this;
    }

    /**
     * order by 语句
     *
     * @param string|Expr
     * @return $this
     *
     * @example
     *
     * $select->orderBy('foo');
     * $select->orderBy(new Expr('foo desc'));
     * $select->orderBy(['foo' => 'desc', 'bar' => 'asc']);
     * $select->orderBy('foo', 'bar', new Expr('baz desc'));
     */
    public function orderBy($expressions) {
        $expressions = is_array($expressions) ? $expressions : func_get_args();

        $order_by = [];
        foreach ($expressions as $key => $expression) {
            if ($expression instanceof Expr) {
                $order_by[] = $expression;
            } else {
                if (is_numeric($key)) {
                    $column = $expression;
                    $sort = 'ASC';
                } else {
                    $column = $key;
                    $sort = $expression;
                }

                $column = $this->adapter->quoteIdentifier($column);
                $sort = (strtoupper($sort) === 'DESC') ? 'DESC' : '';

                $order_by[] = $sort ? $column.' '.$sort : $column;
            }
        }

        $this->order_by = $order_by;
        return $this;
    }

    /**
     * limit语句
     *
     * @param integer $count
     * @return $this
     */
    public function limit($count) {
        $this->limit = abs((int)$count);
        return $this;
    }

    /**
     * offset语句
     *
     * @param integer $count
     * @param $this
     */
    public function offset($count) {
        $this->offset = abs((int)$count);
        return $this;
    }

    /**
     * 执行查询，返回查询结果句柄对象
     *
     * @return \Owl\Service\DB\Statement
     */
    public function execute() {
        list($sql, $params) = $this->compile();
        return $this->adapter->execute($sql, $params);
    }

    /**
     * 根据当前查询对象的各项参数，编译为具体的select语句及查询参数
     *
     * @return
     * array(
     *     (string),    // select语句
     *     (array)      // 查询参数值
     * )
     */
    public function compile() {
        $adapter = $this->adapter;
        $sql = 'SELECT ';
        $params = [];

        $sql .= $this->columns
              ? implode(', ', $adapter->quoteIdentifier($this->columns))
              : '*';

        list($table, $table_params) = $this->compileFrom();
        if ($table_params) {
            $params = array_merge($params, $table_params);
        }

        $sql .= ' FROM '. $table;

        list($where, $where_params) = $this->compileWhere();
        if ($where) {
            $sql .= ' WHERE '. $where;
        }

        if ($where_params) {
            $params = array_merge($params, $where_params);
        }

        list($group_by, $group_params) = $this->compileGroupBy();
        if ($group_by) {
            $sql .= ' '.$group_by;
        }

        if ($group_params) {
            $params = array_merge($params, $group_params);
        }

        if ($this->order_by) {
            $sql .= ' ORDER BY '. implode(', ', $this->order_by);
        }

        if ($this->limit) {
            $sql .= ' LIMIT '. $this->limit;
        }

        if ($this->offset) {
            $sql .= ' OFFSET '. $this->offset;
        }

        return [$sql, $params];
    }

    /**
     * 查询当前查询条件在表内的行数
     *
     * @return integer
     */
    public function count() {
        $columns = $this->columns;
        $this->columns = array(new Expr('count(1)'));

        $count = $this->execute()->getCol();

        $this->columns = $columns;
        return $count;
    }

    /**
     * 分页，把查询结果限定在指定的页
     *
     * @param integer $page
     * @param integer $size
     * @return $this
     *
     * @example
     * $select->setPage(2, 10)->get();
     */
    public function setPage($page, $size) {
        $this->limit($size)->offset( ($page - 1) * $size );
        return $this;
    }

    /**
     * 分页，直接返回指定页的结果
     *
     * @param integer $page
     * @param integer $size
     * @return array
     *
     * @example
     * $select->getPage(2, 10);
     */
    public function getPage($page, $size) {
        return $this->setPage($page, $size)->get();
    }

    /**
     * 查询数据库数量，计算分页信息
     *
     * @param integer $current  当前页
     * @param integer $size     每页多少条
     * @param integer [$total]  一共有多少条，不指定就到数据库内查询
     * @return
     * array(
     *  'total' => (integer),       // 一共有多少条数据
     *  'size' => (integer),        // 每页多少条
     *  'from' => (integer),        // 本页开始的序号
     *  'to' => (integer),          // 本页结束的序号
     *  'first' => 1,               // 第一页
     *  'prev' => (integer|null),   // 上一页，null说明没有上一页
     *  'current' => (integer),     // 本页页码
     *  'next' => (integer|null),   // 下一页，null说明没有下一页
     *  'last' => (integer),        // 最后一页
     * )
     */
    public function getPageInfo($current, $size, $total = null) {
        if ($total === null) {
            $limit = $this->limit;
            $offset = $this->offset;
            $order = $this->order_by;

            $this->orderBy(null)->limit(0)->offset(0);

            $total = $this->count();

            $this->orderBy($order)->limit($limit)->offset($offset);
        }

        return self::buildPageInfo($total, $size, $current);
    }

    /**
     * 设置预处理函数
     *
     * @param Callable $processor
     * @return $this
     */
    public function setProcessor($processor) {
        if ($processor && !is_callable($processor)) {
            throw new \UnexpectedValueException('Select processor is not callable');
        }

        $this->processor = $processor;
        return $this;
    }

    /**
     * 用预处理函数处理查询到的行
     *
     * @param array $row
     * @return mixed
     */
    public function process(array $row) {
        return $this->processor
             ? call_user_func($this->processor, $row)
             : $row;
    }

    /**
     * 获得所有的查询结果
     *
     * @param integer [$limit]
     * @return array
     */
    public function get($limit = null) {
        if ($limit !== null) {
            $this->limit($limit);
        }

        $sth = $this->execute();
        $processor = $this->processor;

        $records = array();
        while ($record = $sth->getRow()) {
            $records[] = $processor
                       ? call_user_func($processor, $record)
                       : $record;
        }

        return $records;
    }

    /**
     * 只查询返回第一行数据
     *
     * @return mixed
     */
    public function getOne() {
        $records = $this->get(1);
        return array_shift($records);
    }


    /**
     * 根据当前的条件，删除相应的数据
     *
     * 注意：直接利用select删除数据可能不是你想要的结果
     * <code>
     * // 找出符合条件的前5条
     * // select * from "users" where id > 100 order by create_time desc limit 5
     * $select = $adapter->select('users')->where('id > ?', 100)->orderBy('create_time desc')->limit(5);
     *
     * // 因为DELETE语句不支持order by / limit / offset
     * // 删除符合条件的，不仅仅是前5条
     * // delete from "users" where id > 100
     * $select->delete()
     *
     * // 如果要删除符合条件的前5条
     * // delete from "users" where id in (select id from "users" where id > 100 order by create_time desc limit 5)
     * $adapter->select('users')->whereIn('id', $select->setcolumns('id'))->delete();
     * </code>
     * 这里很容易犯错，考虑是否不提供delete()和update()方法
     * 或者发现定义了limit / offset就抛出异常中止
     *
     * @return integer      affected row count
     */
    public function delete() {
        list($where, $params) = $this->compileWhere();

        // 不允许没有任何条件的delete
        if (!$where) {
            throw new \LogicException('MUST specify WHERE condition before delete');
        }

        // 见方法注释
        if ($this->limit OR $this->offset OR $this->group_by) {
            throw new \LogicException('CAN NOT DELETE while specify LIMIT or OFFSET or GROUP BY');
        }

        return $this->adapter->delete($this->table, $where, $params);
    }

    /**
     * 根据当前查询语句的条件参数更新数据
     *
     * @param array $row
     * @return integer      affected row count
     */
    public function update(array $row) {
        list($where, $params) = $this->compileWhere();

        // 不允许没有任何条件的update
        if (!$where) {
            throw new \LogicException('MUST specify WHERE condition before update');
        }

        // 见delete()方法注释
        if ($this->limit OR $this->offset OR $this->group_by) {
            throw new \LogicException('CAN NOT UPDATE while specify LIMIT or OFFSET or GROUP BY');
        }

        return $this->adapter->update($this->table, $row, $where, $params);
    }

    /**
     * 以iterator的形式返回查询结果
     * 通过遍历iterator的方式处理查询结果，避免过大的内存占用
     *
     * @return void
     */
    public function iterator() {
        $res = $this->execute();

        while ($row = $res->fetch()) {
            yield $this->process($row);
        }
    }

    /**
     * 分批遍历查询结果
     * 每批次获取一定数量，遍历完一批再继续下一批
     *
     * 避免mysql buffered query遍历巨大的查询结果导致的内存溢出问题
     *
     * @param integer $size
     * @return void
     */
    public function batchIterator($size = 1000) {
        $limit_copy = $this->limit;
        $offset_copy = $this->offset;

        $limit = $limit_copy ?: -1;
        $offset = $offset_copy;

        if (0 < $limit && $limit < $size) {
            $size = $limit;
        }

        try {
            do {
                $res = $this->limit($size)->offset($offset)->execute();

                $found = false;
                while ($row = $res->fetch()) {
                    if ($limit-- === 0) {
                        break;
                    }

                    $found = true;

                    yield $this->process($row);
                }

                $offset += $size;
            } while ($found);
        } finally {
            // retrieve limit & offset
            $this->limit($limit_copy)->offset($offset_copy);
        }
    }

    //////////////////// protected method ////////////////////

    /**
     * where in 子查询语句
     *
     * @param string $column
     * @param array|Select $relation
     * @param boolean $in
     * @return $this
     */
    protected function whereSub($column, $relation, $in) {
        $column = $this->adapter->quoteIdentifier($column);
        $params = [];

        if ($relation instanceof Select) {
            list($sql, $params) = $relation->compile();
            $sub = $sql;
        } else {
            $sub = implode(',', $this->adapter->quote($relation));
        }

        $where = $in
               ? sprintf('%s IN (%s)', $column, $sub)
               : sprintf('%s NOT IN (%s)', $column, $sub);

        $this->where[] = [$where, $params];
        return $this;
    }

    /**
     * 把from参数编译为select 子句
     *
     * @return
     * array(
     *     (string),    // from 子句
     *     (array),     // 查询参数
     * )
     */
    protected function compileFrom() {
        $params = array();

        if ($this->table instanceof Select) {
            list($sql, $params) = $this->table->compile();
            $table = sprintf('(%s) AS %s', $sql, $this->adapter->quoteIdentifier(uniqid()));
        } elseif ($this->table instanceof Expr) {
            $table = (string)$this->table;
        } else {
            $table = $this->adapter->quoteIdentifier($this->table);
        }

        return [$table, $params];
    }

    /**
     * 把查询条件参数编译为where子句
     *
     * @return
     * array(
     *     (string),    // where 子句
     *     (array),     // 查询参数
     * )
     */
    protected function compileWhere() {
        if (!$this->where) {
            return ['', []];
        }

        $where = $params = [];

        foreach ($this->where as $w) {
            list($where_sql, $where_params) = $w;

            $where[] = $where_sql;
            if ($where_params) {
                $params = array_merge($params, $where_params);
            }
        }
        $where = '('. implode(') AND (', $where) .')';
        return [$where, $params];
    }

    /**
     * 编译group by 子句
     *
     * @return
     * array(
     *     (string),    // group by 子句
     *     (array),     // 查询参数
     * )
     */
    protected function compileGroupBy() {
        if (!$this->group_by) {
            return ['', []];
        }

        list($group_columns, $having, $having_params) = $this->group_by;

        $group_columns = $this->adapter->quoteIdentifier($group_columns);
        if (is_array($group_columns)) {
            $group_columns = implode(',', $group_columns);
        }

        $sql = 'GROUP BY '. $group_columns;
        if ($having) {
            $sql .= ' HAVING '. $having;
        }

        return [$sql, $having_params];
    }

    static public function buildPageInfo($total, $page_size, $current_page = 1) {
        $total = (int)$total;
        $page_size = (int)$page_size;
        $current_page = (int)$current_page;

        $page_count = ceil($total / $page_size) ?: 1;

        if ($current_page > $page_count) {
            $current_page = $page_count;
        } elseif ($current_page < 1) {
            $current_page = 1;
        }

        $page = array(
            'total' => $total,
            'size' => $page_size,
            'from' => 0,
            'to' => 0,
            'first' => 1,
            'prev' => null,
            'current' => $current_page,
            'next' => null,
            'last' => $page_count,
        );

        if ($current_page > $page['first'])
            $page['prev'] = $current_page - 1;

        if ($current_page < $page['last'])
            $page['next'] = $current_page + 1;

        if ($total) {
            $page['from'] = ($current_page - 1) * $page_size + 1;
            $page['to'] = $current_page == $page['last']
                        ? $total
                        : $current_page * $page_size;
        }

        return $page;
    }
}
