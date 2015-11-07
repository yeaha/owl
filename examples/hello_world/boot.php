<?php
// 调试开关，正式部署时应该设置为false
defined('DEBUG') or define('DEBUG', true);

// 单元测试开关
defined('TEST') or define('TEST', false);

// 是否网站模式
defined('SITE_MODE') or define('SITE_MODE', false);

define('ROOT_DIR', __DIR__);

require __DIR__.'/vendor/autoload.php';

\Owl\Application::registerNamespace('\\', __DIR__);

set_error_handler(function($errno, $error, $file = null, $line = null) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($error, $errno, $errno, $file, $line);
    }

    return true;
});

if (!SITE_MODE) {
    __bootstrap();
}

function __bootstrap() {
    static $boot = false;

    if ($boot) {
        return true;
    }

    $boot = true;

    // 加载配置文件
    \Owl\Config::merge(require ROOT_DIR.'/config/main.php');

    // 初始化外部服务容器
    \Owl\Service\Container::getInstance()->setServices(\Owl\Config::get('services'));
}

function __ini_app(\Owl\Application $app) {
    $app->middleware(function($request, $response) {
        $start = microtime(true);

        yield;

        $use_time = (microtime(true) - $start) * 1000;
        $response->withHeader('use-time', (int)$use_time.'ms');
    });

    $router = new \Owl\Mvc\Router([
        'namespace' => '\Controller',
    ]);
    $app->middleware(function($request, $response) use ($router) {
        $router->execute($request, $response);
    });

    $app->setExceptionHandler(function($exception, $request, $response) {
        $status = 500;
        if ($exception instanceof \Owl\Http\Exception) {
            $status = $exception->getCode();
        }

        $response->withStatus($status);

        $view = new \Owl\Mvc\View(ROOT_DIR.'/View');
        $response->write($view->render('_error', ['exception' => $exception]));
    });

    return $app;
}

function __get_fpm_app() {
    static $app;

    if (!$app) {
        __bootstrap();

        $app = new \Owl\Application;
        $app = __ini_app($app);
    }

    return $app;
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

    // 在workstart之后再bootstrap，就可以通过server reload重置应用配置
    $server->on('workerstart', function() {
        __bootstrap();
    });

    return __ini_app($app);
}
