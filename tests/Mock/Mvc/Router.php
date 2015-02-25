<?php
namespace Tests\Mock\Mvc;

class Router extends \Owl\Mvc\Router {
    public function testDispatch($path) {
        return $this->dispatch($path);
    }
}
