<?php
require __DIR__.'/boot.php';

$config = parse_ini_file(ROOT_DIR.'/server.ini', true);

$ip = $config['app_listener']['ip'];
$port = $config['app_listener']['port'];
$app = new \Owl\Swoole\Application($ip, $port);
$app = __ini_app($app);

if (isset($config['swoole_setting']) && $config['swoole_setting']) {
    $app->getSwooleServer()->set($config['swoole_setting']);
}

echo sprintf("Listening http://%s:%d/ ...\n", $ip, $port);
$app->start();
