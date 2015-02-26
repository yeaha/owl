<?php
require __DIR__.'/boot.php';

$app = new \Owl\Application();
$app = __ini_app($app);
$app->start();
