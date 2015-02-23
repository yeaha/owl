<?php
require __DIR__ .'/../src/Application.php';

\Owl\Application::registerNamespace('\Owl', __DIR__.'/../src');

$ip = '127.0.0.1';
$port = 12345;

$app = new \Owl\Application($ip, $port);

$app->middleware(function($request, $response, $next) {
    $start = microtime(true);

    yield $next;

    $use_time = (microtime(true) - $start) * 1000;
    $response->setHeader('use-time', (int)$use_time.'ms');
});

$app->middleware(function($request, $response, $next) {
    if ($request->getRequestPath() === '/') {
        $response->setBody('hello world!');
    } else {
        throw \Owl\Http\Exception::factory(404);
    }

    yield $next;
});

$app->setExceptionHandler(function($exception, $request, $response) {
    if ($exception instanceof \Owl\Http\Exception) {
        $status = $exception->getCode();
        $message = $exception->getMessage();
    } else {
        $status = 500;
        $message = \Owl\Http::getStatusMessage(500);
    }

    $response->setStatus($status);
    $response->setBody($message);
});

echo sprintf("Listening http://%s:%d ...\n", $ip, $port);
$app->start();
