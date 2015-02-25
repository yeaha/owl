<?php
defined('TEST') or define('TEST', true);

require __DIR__ .'/../src/autoload.php';

\Owl\Application::registerNamespace('\\Tests', __DIR__);
