<?php
namespace Controller;

class Index extends \Controller {
    public function GET($request, $response) {
        return $this->render('Index', ['output' => 'hello world!']);
    }
}
