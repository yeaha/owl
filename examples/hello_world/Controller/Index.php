<?php
namespace Controller;

class Index extends \Controller {
    public function GET($request, $response) {
        $response->setCookie('foo', 'bar');
        return $this->render('Index', ['output' => 'hello world!']);
    }
}
