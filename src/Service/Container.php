<?php
namespace Owl\Service;

/**
 * @example
 * $container = \Owl\Service\Container::getInstance();
 *
 * $container->setServices([
 *     'mysql.master' => [
 *         'class' => '\Owl\Service\DB\Mysql\Adapter',
 *         'dsn' => 'mysql:host=192.168.1.2;dbname=foobar',
 *         'user' => 'root',
 *         'password' => 'password',
 *     ],
 *     'mysql.slave.1' => [
 *         'class' => '\Owl\Service\DB\Mysql\Adapter',
 *         'dsn' => 'mysql:host=192.168.1.3;dbname=foobar',
 *         'user' => 'root',
 *         'password' => 'password',
 *     ],
 *     'mysql.slave.2' => [
 *         'class' => '\Owl\Service\DB\Mysql\Adapter',
 *         'dsn' => 'mysql:host=192.168.1.4;dbname=foobar',
 *         'user' => 'root',
 *         'password' => 'password',
 *     ],
 * ]);
 *
 * $container->setRouter('mysql.slave', function($id) {
 *     $service_id = ($id % 2) ? 1 : 2;
 *
 *     return $this->get('mysql.slave.'. $service_id);
 * });
 *
 * $master = $container->get('mysql.master');
 * $slave = $container->get('mysql.slave', 123);
 */
class Container extends \Owl\Container {
    use \Owl\Traits\Singleton;

    protected $router = [];

    public function setServices(array $services) {
        foreach ($services as $id => $options) {
            $this->setService($id, $options);
        }

        return $this;
    }

    public function setRouter($id, \Closure $handler) {
        $handler->bindTo($this);

        $this->router[$id] = $handler;
        return $this;
    }

    public function get($id) {
        if ($this->has($id)) {
            return parent::get($id);
        }

        if (!isset($this->router[$id])) {
            throw new \Exception('Undefined service or service router: "'.$id.'"');
        }

        $args = array_slice(func_get_args(), 1);
        $handler = $this->router[$id];

        return call_user_func_array($handler, $args);
    }

    public function reset() {
        parent::reset();
        $this->router = [];
    }

    protected function setService($id, array $options) {
        $this->set($id, function() use ($options) {
            if (!isset($options['class'])) {
                throw new \InvalidArgumentException();
            }

            $class = $options['class'];
            if (!is_subclass_of($class, '\Owl\Service')) {
                throw new \UnexpectedValueException();
            }

            unset($options['class']);
            return new $class($options);
        });
    }
}
