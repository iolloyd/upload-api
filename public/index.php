<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

// Always start in the project directory
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

// Init Silex Application
require 'bootstrap.php';
// run
$app->boot();
$app['load']('routes');

$app->run();
