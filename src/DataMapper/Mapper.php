<?php
namespace Owl\DataMapper;

use Owl\DataMapper\Registry;

abstract class Mapper {
    /**
     * Data class名
     * @var string
     */
    protected $class;

    /**
     * 配置，存储服务、存储集合、属性定义等等
     * @var array
     */
    protected $options = [];

    /**
     * 根据主键值返回查询到的单条记录
     *
     * @param string|integer|array $id 主键值
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     * @return array 数据结果
     */
    abstract protected function doFind($id, \Owl\Service $service = null, $collection = null);

    /**
     * 插入数据到存储服务
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     * @return array 新的主键值
     */
    abstract protected function doInsert(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * 更新数据到存储服务
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     * @return boolean
     */
    abstract protected function doUpdate(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * 从存储服务删除数据
     *
     * @param Data $data Data实例
     * @param Owl\Service [$service] 存储服务连接
     * @param string [$collection] 存储集合名
     * @return boolean
     */
    abstract protected function doDelete(\Owl\DataMapper\Data $data, \Owl\Service $service = null, $collection = null);

    /**
     * @param string $class
     */
    public function __construct($class) {
        $this->class = $class;
        $this->options = $this->normalizeOptions($class::getOptions());
    }

    public function __before($event, \Owl\DataMapper\Data $data) {
        call_user_func([$data, '__before'.$event]);
    }

    public function __after($event, \Owl\DataMapper\Data $data) {
        call_user_func([$data, '__after'.$event]);
    }

    /**
     * 指定的配置是否存在
     *
     * @param string $key
     * @return boolean
     */
    public function hasOption($key) {
        return isset($this->options[$key]);
    }

    /**
     * 获取指定的配置内容
     *
     * @param string $key
     * @return mixed
     * @throws \RuntimeException 指定的配置不存在
     */
    public function getOption($key) {
        if (!isset($this->options[$key])) {
            throw new \RuntimeException('Mapper: undefined option "'.$key.'"');
        }

        return $this->options[$key];
    }

    /**
     * 获取所有的配置内容
     *
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * 获得存储服务连接实例
     *
     * @return Owl\Service
     * @throws \RuntimeException Data class没有配置存储服务
     */
    public function getService() {
        $service = $this->getOption('service');
        return \Owl\Service\Container::getInstance()->get($service);
    }

    /**
     * 获得存储集合的名字
     * 对于数据库来说，就是表名
     *
     * @return string
     * @throws \RuntimeException 存储集合名未配置
     */
    public function getCollection() {
        return $this->getOption('collection');
    }

    /**
     * 获得主键定义
     *
     * @return
     * [
     *     (string) => array,  // 主键字段名 => 属性定义
     * ]
     */
    public function getPrimaryKey() {
        return $this->getOption('primary_key');
    }

    /**
     * 获得指定属性的定义
     *
     * @param string $key 属性名
     * @return array|false
     */
    public function getAttribute($key) {
        return isset($this->options['attributes'][$key])
             ? $this->options['attributes'][$key]
             : false;
    }

    /**
     * 获得所有的属性定义
     * 默认忽略被标记为“废弃”的属性
     *
     * @param boolean $without_deprecated 不包含废弃属性
     * @return [
     *     (string) => (array),  // 属性名 => 属性定义
     *     ...
     * ]
     */
    public function getAttributes($without_deprecated = true) {
        $attributes = $this->getOption('attributes');

        if ($without_deprecated) {
            foreach ($attributes as $key => $attribute) {
                if ($attribute['deprecated']) {
                    unset($attributes[$key]);
                }
            }
        }

        return $attributes;
    }

    /**
     * 是否定义了指定的属性
     * 如果定义了属性，但被标记为“废弃”，也返回未定义
     *
     * @param string $key 属性名
     * @return boolean
     */
    public function hasAttribute($key) {
        $attribute = $this->getAttribute($key);
        return $attribute ? !$attribute['deprecated'] : false;
    }

    /**
     * Mapper是否只读
     *
     * @return boolean
     */
    public function isReadonly() {
        return $this->getOption('readonly');
    }

    /**
     * 把存储服务内获取的数据，打包成Data实例
     *
     * @param array $record
     * @param Data [$data]
     * @return Data
     */
    public function pack(array $record, Data $data = null) {
        $types = Type::getInstance();
        $values = [];

        foreach ($record as $key => $value) {
            $attribute = $this->getAttribute($key);

            if ($attribute && !$attribute['deprecated']) {
                $values[$key] = $types->get($attribute['type'])->restore($value, $attribute);
            }
        }

        if ($data) {
            $data->__pack($values, false);
        } else {
            $class = $this->class;
            $data = new $class(null, ['fresh' => false]);
            $data->__pack($values, true);
        }

        return $data;
    }

    /**
     * 把Data实例内的数据，转换为适用于存储的格式
     *
     * @param Data $data
     * @param array [$options]
     * @return array
     */
    public function unpack(Data $data, array $options = null) {
        $defaults = ['dirty' => false];
        $options = $options ? array_merge($defaults, $options) : $defaults;

        $attributes = $this->getAttributes();

        $record = [];
        foreach ($data->pick(array_keys($attributes)) as $key => $value) {
            if ($options['dirty'] && !$data->isDirty($key)) {
                continue;
            }

            if ($value !== null) {
                $attribute = $attributes[$key];
                $value = Type::factory($attribute['type'])->store($value, $attribute);
            }

            $record[$key] = $value;
        }

        return $record;
    }

    /**
     * 根据指定的主键值生成Data实例
     *
     * @param string|integer|array $id 主键值
     * @param Data [$data]
     * @return Data|false
     */
    public function find($id, Data $data = null) {
        $registry = Registry::getInstance();

        if (!$data) {
            if ($data = $registry->get($this->class, $id)) {
                return $data;
            }
        }

        if (!$record = $this->doFind($id)) {
            return false;
        }

        $data = $this->pack($record, $data ?: null);
        $registry->set($data);

        return $data;
    }

    /**
     * 从存储服务内重新获取数据并刷新Data实例
     *
     * @param Data $data
     * @return Data
     */
    public function refresh(Data $data) {
        if ($data->isFresh()) {
            return $data;
        }

        return $this->find($data->id(), $data);
    }

    /**
     * 保存Data
     *
     * @param Data $data
     * @return boolean
     */
    public function save(Data $data) {
        if ($this->isReadonly()) {
            throw new \RuntimeException($this->class.' is readonly');
        }

        $is_fresh = $data->isFresh();
        if (!$is_fresh && !$data->isDirty()) {
            return true;
        }

        $this->__before('save', $data);

        $result = $is_fresh ? $this->insert($data) : $this->update($data);
        if (!$result) {
            throw new \RuntimeException($this->class.' save failed');
        }

        $this->__after('save', $data);

        return true;
    }

    /**
     * 删除Data
     *
     * @param Data $data
     * @return boolean
     */
    public function destroy(Data $data) {
        if ($this->isReadonly()) {
            throw new \RuntimeException($this->class.' is readonly');
        }

        if ($data->isFresh()) {
            return true;
        }

        $this->__before('delete', $data);

        if (!$this->doDelete($data)) {
            throw new \Exception($this->class.' destroy failed');
        }

        $this->__after('delete', $data);

        Registry::getInstance()->remove($this->class, $data->id());

        return true;
    }

    /**
     * 把新的Data数据插入到存储集合中
     *
     * @param Data $data
     * @return boolean
     */
    protected function insert(Data $data) {
        $this->__before('insert', $data);
        $this->validateData($data);

        if (!is_array($id = $this->doInsert($data))) {
            return false;
        }

        $this->pack($id, $data);
        $this->__after('insert', $data);

        return true;
    }

    /**
     * 更新Data数据到存储集合内
     *
     * @param Data $data
     * @return boolean
     */
    protected function update(Data $data) {
        $this->__before('update', $data);
        $this->validateData($data);

        if (!$this->doUpdate($data)) {
            return false;
        }

        $this->pack([], $data);
        $this->__after('update', $data);

        return true;
    }

    /**
     * Data属性值有效性检查
     *
     * @param Data $data
     * @return boolean
     * @throws \UnexpectedValueException 不允许为空的属性没有被赋值
     */
    protected function validateData(Data $data) {
        $is_fresh = $data->isFresh();
        $attributes = $this->getAttributes();

        if ($is_fresh) {
            $record = $this->unpack($data);
            $keys = array_keys($attributes);
        } else {
            $record = $this->unpack($data, ['dirty' => true]);
            $keys = array_keys($record);
        }

        foreach ($keys as $key) {
            $attribute = $attributes[$key];

            do {
                if ($attribute['allow_null']) {
                    break;
                }

                if ($attribute['auto_generate'] && $is_fresh) {
                    break;
                }

                if (isset($record[$key])) {
                    break;
                }

                throw new \UnexpectedValueException($this->class.' property '.$key.' not allow null');
            } while (false);
        }

        return true;
    }

    /**
     * 格式化从Data class获得的配置信息
     *
     * @param array $options
     * @return array
     */
    protected function normalizeOptions(array $options) {
        $options = array_merge([
            'service' => null,
            'collection' => null,
            'attributes' => [],
            'readonly' => false,
            'strict' => false,
        ], $options);

        $primary_key = [];
        foreach ($options['attributes'] as $key => $attribute) {
            $attribute = Type::normalizeAttribute($attribute);

            if ($attribute['strict'] === null) {
                $attribute['strict'] = $options['strict'];
            }

            if ($attribute['primary_key'] && !$attribute['deprecated']) {
                $primary_key[] = $key;
            }

            $options['attributes'][$key] = $attribute;
        }

        if (!$primary_key) {
            throw new \RuntimeException('Mapper: undefined primary key');
        }

        $options['primary_key'] = $primary_key;

        return $options;
    }

    /**
     * Mapper实例缓存数组
     * @var array
     */
    static private $instance = [];

    /**
     * 获得指定Data class的Mapper实例
     *
     * @param string $class
     * @return Mapper
     */
    final static public function factory($class) {
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new static($class);
        }
        return self::$instance[$class];
    }
}
