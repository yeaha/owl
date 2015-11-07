<?php
define('SITE_MODE', true);

require __DIR__.'/boot.php';

$app = __get_fpm_app();
$app->start();
