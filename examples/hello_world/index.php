<?php
defined('DEBUG') or define('DEBUG', true);
define('ROOT_DIR', __DIR__);

require __DIR__.'/../../src/autoload.php';

\Owl\Application::registerNamespace('\\', __DIR__);

$app = new \Owl\Application();

$app->middleware(function($request, $response) {
    $start = microtime(true);

    yield true;

    $use_time = (microtime(true) - $start) * 1000;
    $response->setHeader('use-time', (int)$use_time.'ms');
});

$router = new \Owl\Mvc\Router([
    'namespace' => [
        '/' => '\Controller',
    ],
]);
$app->middleware(function($request, $response) use ($router) {
    $router->execute($request, $response);

    yield true;
});

$app->setExceptionHandler(function($exception, $request, $response) {
    $status = 500;
    if ($exception instanceof \Owl\Http\Exception) {
        $status = $exception->getCode();
    }

    $response->setStatus($status);
    $response->setBody($exception->getMessage());
});

$app->start();
