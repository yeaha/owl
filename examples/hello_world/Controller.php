<?php
abstract class Controller {
    protected function checkParameters(array $parameters, array $options) {
        try {
            (new \Owl\Parameter\Checker)->execute($parameters, $options);
        } catch (\Owl\Parameter\Exception $exception) {
            throw \Owl\Http\Exception::factory(400, $exception);
        }
    }

    protected function render($file, array $vars = []) {
        $view = new \Owl\Mvc\View(ROOT_DIR.'/View');
        return $view->render($file, $vars);
    }
}
