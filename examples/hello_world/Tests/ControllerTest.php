<?php
namespace Tests;

abstract class ControllerTest extends \PHPUnit_Framework_TestCase {
    protected function execute(array $options) {
        $path = isset($options['uri']) ? parse_url($options['uri'], PHP_URL_PATH) : '/';
        $cookies = \Tests\Mock\Cookie::getInstance()->get($path);

        if (isset($options['cookies'])) {
            $options['cookies'] = array_merge($cookies, $options['cookies']);
        } else {
            $options['cookies'] = $cookies;
        }

        $request = \Owl\Http\Request::factory($options);
        $response = new \Tests\Mock\Response;

        $app = __get_fpm_app();
        $app->execute($request, $response);

        return $response;
    }

    protected function resetCookie() {
        \Tests\Mock\Cookie::getInstance()->reset();
    }
}
