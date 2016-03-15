<?php

namespace Owl;

/**
 * 依赖注入容器.
 *
 * @example
 *
 * $container = new \Owl\Container;
 *
 * $container->set('foo', function() {
 *     return 'bar';
 * });
 *
 * var_dump($container->get('foo') === 'bar');
 */
class Container
{
    /**
     * 保存用 set 方法注册的回调方法.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * 每个回调方法的执行结果会被缓存到这个数组里.
     *
     * @var array
     */
    protected $values = [];

    /**
     * @param string  $id
     * @param Closure $callback
     */
    public function set($id, \Closure $callback)
    {
        $this->callbacks[$id] = $callback;
    }

    /**
     * 检查是否存在指定的注入内容.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->callbacks[$id]);
    }

    /**
     * 从容器内获得注册的回调方法执行结果.
     *
     * 注意：
     * 注册的回调方法只会执行一次，即每次get都拿到同样的结果
     *
     * @param string $id
     *
     * @return mixed
     *
     * @throws 指定的$id不存在时
     */
    public function get($id)
    {
        if (isset($this->values[$id])) {
            return $this->values[$id];
        }

        $callback = $this->getCallback($id);
        $value = call_user_func($callback);

        return $this->values[$id] = $value;
    }

    /**
     * 删除容器内的成员，包括回调的执行结果.
     *
     * @param string $id
     *
     * @return bool
     */
    public function remove($id)
    {
        unset($this->callbacks[$id]);
        unset($this->values[$id]);

        return true;
    }

    /**
     * 获得指定名字的回调函数.
     *
     * @param string $id
     *
     * @return Closuer
     *
     * @throws 指定的$id不存在时
     */
    public function getCallback($id)
    {
        if ($this->has($id)) {
            return $this->callbacks[$id];
        }

        throw new \UnexpectedValueException(sprintf('"%s" does not exists in container', $id));
    }

    /**
     * 重置整个容器，清空内容.
     */
    public function reset()
    {
        $this->callbacks = [];
        $this->values = [];
    }

    /**
     * 刷新所有的执行结果.
     */
    public function refresh()
    {
        $this->values = [];
    }
}
