<?php
namespace Owl;

/**
 * 中间件
 *
 * @example
 *
 * $middleware = new \Owl\Middleware;
 *
 * $middleware->insert(function($a, $b, $c) {
 *     echo "before 1\n";
 *
 *     yield true;      // continue execute next middleware
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
 *     yield true;
 *
 *     echo "after 3\n";
 * });
 *
 * // 这里传递的参数会在调用时传递给每个中间件函数
 * $middleware->execute(1, 2, 3);
 *
 * // 执行结果:
 * // before 1
 * // before 2
 * // after 2
 * // after 1
 */
class Middleware {
    protected $handlers = [];

    public function insert($handler) {
        if (!is_callable($handler)) {
            throw new \Exception('Middleware handler is not callable.');
        }

        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * @return void
     */
    public function execute() {
        if (!$this->handlers) {
            return;
        }

        $args = func_get_args();

        $stack = [];
        foreach ($this->handlers as $handler) {
            $generator = call_user_func_array($handler, $args);

            if (!$generator || !($generator instanceof \Generator)) {
                throw new \Exception('Middleware handler need "yield"!');
            }

            $stack[] = $generator;

            if (!$generator->current()) {
                break;
            }
        }

        while ($generator = array_pop($stack)) {
            $generator->next();
        }
    }

    public function reset() {
        $this->handlers = [];
    }
}
