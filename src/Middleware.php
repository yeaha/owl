<?php
namespace Owl;

/**
 * 中间件.
 *
 * @example
 *
 * $middleware = new \Owl\Middleware;
 *
 * $middleware->insert(function($a, $b, $c) {
 *     echo "before 1\n";
 *
 *     yield;           // continue execute next middleware
 *
 *     echo "after 1\n";
 * });
 *
 * $middleware->insert(function($a, $b, $c) {
 *     echo "before 2\n";
 *
 *     yield false;     // stop execute next middleware function
 *
 *     echo "after 2\n";
 * });
 *
 * $middleware->insert(function($a, $b, $c) {
 *     echo "before 3\n";
 *
 *     yield;
 *
 *     echo "after 3\n";
 * });
 *
 * // before 1
 * // before 2
 * // after 2
 * // after 1
 * $middleware->execute(1, 2, 3);
 */
class Middleware
{
    protected $handlers = [];

    /**
     * 添加一个新的中间件到队列中.
     *
     * @param callable $handler
     *
     * @return $this
     */
    public function insert($handler)
    {
        if (!is_callable($handler)) {
            throw new \Exception('Middleware handler is not callable.');
        }

        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * 执行队列里的所有中间件
     * 调用此方法传递的任意参数都会被传递给每个中间件.
     */
    public function execute(array $arguments = [], array $handlers = [])
    {
        $handlers = $handlers ?: $this->handlers;

        if (!$handlers) {
            return;
        }

        $stack = [];
        $result = null;
        foreach ($handlers as $handler) {
            // reset before each loop, only save last handler return value
            $result = null;
            $generator = call_user_func_array($handler, $arguments);

            if ($generator instanceof \Generator) {
                $stack[] = $generator;

                if ($generator->current() === false) {
                    break;
                }
            } elseif ($generator !== null) {
                // return without yield
                $result = $generator;
            }
        }

        $return = ($result !== null);
        while ($generator = array_pop($stack)) {
            if ($return) {
                $generator->send($result);
            }

            $generator->next();

            if (!$return) {
                $return = true;
                $result = $generator->getReturn();
            }
        }

        return $result;
    }

    /**
     * 清空中间件队列.
     */
    public function reset()
    {
        $this->handlers = [];
    }
}
