<?php
namespace Controller;

class Index {
    public function GET($request, $response) {
        $view = new \Owl\Mvc\View(ROOT_DIR.'/View');
        return $view->render('Index', ['output' => 'hello world!']);
    }
}
