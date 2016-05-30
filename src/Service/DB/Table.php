<?php
namespace Owl\Service\DB;

class Table
{
    protected $adapter;
    protected $table_name;
    protected $columns;
    protected $indexes;

    public function __construct(Adapter $adapter, $table_name)
    {
        $this->adapter = $adapter;
        $this->table_name = $table_name;
    }

    /**
     * 获得表名
     *
     * @return string
     */
    public function getName()
    {
        return $this->table_name;
    }

    /**
     * 获得数据库连接对象
     *
     * @return \Owl\Service\DB\Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * 获得字段信息
     *
     * @see \Owl\Service\DB\Adapter
     * @return array
     */
    public function getColumns()
    {
        if ($this->columns === null) {
            $this->columns = $this->adapter->getColumns($this->table_name);
        }

        return $this->columns;
    }

    /**
     * 检查字段是否存在
     *
     * @param string $column_name
     * @return bool
     */
    public function hasColumn($column_name)
    {
        $columns = $this->getColumns();

        return isset($columns[$column_name]);
    }

    /**
     * 获得索引信息
     *
     * @return array
     */
    public function getIndexes()
    {
        if ($this->indexes === null) {
            $this->indexes = $this->adapter->getIndexes($this->table_name);
        }

        return $this->indexes;
    }

    /**
     * 检查索引是否存在
     *
     * @param string $index_name
     * @return bool
     */
    public function hasIndex($index_name)
    {
        foreach ($this->getIndexes() as $index) {
            if ($index['name'] === $index_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获得指定字段上的索引
     *
     * @param string $column_name
     * @return array
     */
    public function getIndexesOfColumn($column_name)
    {
        $result = [];

        foreach ($this->getIndexes() as $index) {
            if (in_array($column_name, $index['columns'])) {
                $result[] = $index;
            }
        }

        return $result;
    }

    /**
     * 创建查询对象
     *
     * @return \Owl\Service\DB\Select
     */
    public function select()
    {
        return $this->adapter->select($this->table_name);
    }

    /**
     * 插入一条记录，返回插入的行数
     *
     * @param array $row
     * @return int
     */
    public function insert(array $row)
    {
        return $this->adapter->insert($this->table_name, $row);
    }

    /**
     * 更新记录，允许指定条件
     * 返回被更新的行数
     *
     * @param array $row
     * @param string $where
     * @param mixed $parameters
     * @return int
     */
    public function update(array $row/*, $where = null, $parameters = null*/)
    {
        $args = func_get_args();
        array_unshift($args, $this->table_name);

        return call_user_func_array([$this->adapter, 'update'], $args);
    }

    /**
     * 删除记录，允许指定条件
     *
     * @param string $where
     * @param mixed $parameters
     * @return int
     */
    public function delete( /*$where = null, $parameters = null*/)
    {
        $args = func_get_args();
        array_unshift($args, $this->table_name);

        return call_user_func_array([$this->adapter, 'delete'], $args);
    }
}
