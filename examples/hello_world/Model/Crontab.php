<?php
namespace Model;

abstract class Crontab extends \Owl\Crontab {
    public function __construct($name = null) {
        if ($name) {
            $this->name = $name;
        }

        $this->setContextHandler(new \Owl\Context\Redis([
            'token' => 'job:'.$this->getName(),
            'service' => \Owl\Service\Container::getInstance()->get('redis'),
            'ttl' => 7 * 86400,
        ]));
    }
}
