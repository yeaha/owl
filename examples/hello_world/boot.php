<?php
defined('DEBUG') or define('DEBUG', true);
define('ROOT_DIR', __DIR__);

require __DIR__.'/../../src/autoload.php';

\Owl\Application::registerNamespace('\\', __DIR__);

function __ini_app(\Owl\Application $app) {
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

        $view = new \Owl\Mvc\View(ROOT_DIR.'/View');
        $response->setBody($view->render('_error', ['exception' => $exception]));
    });

    return $app;
}

function __get_fpm_app() {
    $app = new \Owl\Application;

    return __ini_app($app);
}

function __get_swoole_app() {
    $config = parse_ini_file(ROOT_DIR.'/server.ini', true);

    $ip = $config['app_listener']['ip'];
    $port = $config['app_listener']['port'];
    $app = new \Owl\Swoole\Application($ip, $port);

    if (isset($config['swoole_setting']) && $config['swoole_setting']) {
        $app->getSwooleServer()->set($config['swoole_setting']);
    }

    $app->getSwooleServer()->on('start', function() use ($config) {
        echo sprintf("Listening http://%s:%d/ ...\n", $config['app_listener']['ip'], $config['app_listener']['port']);
    });

    return __ini_app($app);
}
