<?php
error_reporting(E_ALL);
ini_set('display_errors', true);
// Always start in the project directory
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' 
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

// Init autoloading
require 'autoload.php';
require 'bootstrap_doctrine.php';


/*
 * Slim Application
 */
ini_set('session.name', 'CLOUD');
ini_set('session.cookie_httponly', true);

session_cache_limiter(false);
session_start();

$app = new \Cloud\Slim\Slim();

// loader
$loader = new \Cloud\Slim\Loader\Loader();
$loader->load('config')
       ->load('helper')
       ->load('routes')
       ->load('controllers')
       ->into($app);

// middleware
$app->add(new \Cloud\Slim\Middleware\Session());
$app->add(new \Slim\Middleware\ContentTypes());

// run
$app->run();
