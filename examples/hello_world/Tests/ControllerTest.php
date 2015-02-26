<?php
namespace Tests;

abstract class ControllerTest extends \PHPUnit_Framework_TestCase {
    protected function execute(array $options) {
        $app = __get_fpm_app();

        $request = \Owl\Http\Request::factory($options);
        $response = new \Owl\Http\Response;

        $app->execute($request, $response);

        return $response;
    }
}
