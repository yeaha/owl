<?php

if (!file_exists(__DIR__.'/../vendor')) {
    die('run "composer install" first'.PHP_EOL);
}

defined('TEST') or define('TEST', true);
define('TEST_DIR', __DIR__);

require __DIR__.'/../vendor/autoload.php';

\Owl\Application::registerNamespace('\\Tests', __DIR__);
