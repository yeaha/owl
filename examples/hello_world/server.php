<?php
require __DIR__.'/boot.php';

$app = __get_swoole_app();
$app->start();
