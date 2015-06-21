<?php
defined('DEBUG') or define('DEBUG', true);
defined('TEST') or define('TEST', false);
define('ROOT_DIR', __DIR__);

require __DIR__.'/../../src/autoload.php';

\Owl\Application::registerNamespace('\\', __DIR__);

set_error_handler(function($errno, $error, $file = null, $line = null) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($error, $errno, $errno, $file, $line);
    }

    return true;
});

function __ini_app(\Owl\Application $app) {
    $app->middleware(function($request, $response) {
        $start = microtime(true);

        yield true;

        $use_time = (microtime(true) - $start) * 1000;
        $response->setHeader('use-time', (int)$use_time.'ms');
    });

    $router = new \Owl\Mvc\Router([
        'namespace' => '\Controller',
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

function __get_swoole_app(array $config) {
    $app = new \Owl\Swoole\Application($config['server']['ip'], $config['server']['port']);

    if (isset($config['swoole_setting']) && $config['swoole_setting']) {
        $app->getSwooleServer()->set($config['swoole_setting']);
    }

    $server = $app->getSwooleServer();

    $server->on('start', function() use ($config) {
        $pid = posix_getpid();

        if (isset($config['server']['pid_file'])) {
            file_put_contents($config['server']['pid_file'], $pid);
        }

        echo sprintf("Server PID: %d\n", $pid);
        echo sprintf("Listening http://%s:%d/ ...\n", $config['server']['ip'], $config['server']['port']);
    });

    $server->on('shutdown', function() use ($config) {
        if (isset($config['server']['pid_file']) && file_exists($config['server']['pid_file'])) {
            unlink($config['server']['pid_file']);
        }
    });

    return __ini_app($app);
}
