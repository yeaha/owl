<?php
namespace Owl\DataMapper;

use Owl\DataMapper\Data;

class Registry {
    /**
     * 是否开启DataMapper的Data注册表功能
     * @var boolean
     */
    private $enabled = true;

    /**
     * 缓存的Data实例
     * @var array
     */
    private $members = [];

    /**
     * 开启缓存
     * @return void
     */
    public function enable() {
        $this->enabled = true;
    }

    /**
     * 关闭缓存
     * @return void
     */
    public function disable() {
        $this->enabled = false;
    }

    /**
     * 缓存是否开启
     * @return boolean
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * 把Data实例缓存起来
     *
     * @param Data $data
     * @return void
     */
    public function set(Data $data) {
        $class = self::normalizeClassName(get_class($data));
        if (!$this->isEnabled()) {
            return false;
        }

        if ($data->isFresh()) {
            return false;
        }

        if (!$id = $data->id()) {
            return false;
        }

        $key = self::key($class, $id);
        $this->members[$key] = $data;
    }

    /**
     * 根据类名和主键值，获得缓存结果
     *
     * @param string class
     * @param string|integer|array $id
     * @return Data|false
     */
    public function get($class, $id) {
        $class = self::normalizeClassName($class);
        if (!$this->isEnabled()) {
            return false;
        }

        $key = self::key($class, $id);
        return isset($this->members[$key])
             ? $this->members[$key]
             : false;
    }

    /**
     * 删除缓存结果
     *
     * @param string $class
     * @param mixed $id
     * @return void
     */
    public function remove($class, $id) {
        $class = self::normalizeClassName($class);
        if (!$this->isEnabled()) {
            return false;
        }

        $key = self::key($class, $id);
        unset($this->members[$key]);
    }

    /**
     * 把所有的缓存都删除掉
     *
     * @return void
     */
    public function clear() {
        $this->members = array();
    }

    /**
     * 生成缓存数组的key
     *
     * @param string $class
     * @param mixed $id
     * @return string
     */
    static private function key($class, $id) {
        $key = '';
        if (is_array($id)) {
            ksort($id);

            foreach ($id as $prop => $val) {
                if ($key) $key .= ';';
                $key .= "{$prop}:{$val}";
            }
        } else {
            $key = $id;
        }

        return $class.'@'.$key;
    }

    /**
     * 格式化类名字符串
     *
     * @param string $class
     * @return string
     */
    static private function normalizeClassName($class) {
        return trim(strtolower($class), '\\');
    }

    static private $instance;

    static public function getInstance() {
        return self::$instance ?: (self::$instance = new self);
    }
}
