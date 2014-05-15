<?php

ini_set('display_errors', true);
error_reporting(E_ALL);
set_time_limit(0);
date_default_timezone_set('UTC');

chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

require 'autoload.php';
require 'bootstrap.php';

// raw password is foo
$userToken = '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==';

// providers
$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'default' => [
            'http' => true,
            'pattern' => '^/',
            //'security' => $app['debug'] ? false : true,
            'anonymous' => true,
            'users' => $app->share(function () use ($app) {
                return $app['em']->getRepository('cx:user');
            }),
        ],
    ],
]);

$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.storage.options' => [
        'name'            => 'CLOUD',
        'cookie_lifetime' => $app['debug'] ? null : '2h',
        'cookie_httponly' => true,
    ],
]);

$app->register(new Cloud\Silex\Provider\CorsHeadersServiceProvider(), [
    'cors.options' => [
        'allow_credentials' => true,
        'allow_origin'      => 'http://cloud-ng.local',
        'max_age'           => 604800,
    ],
]);

// run
$app->boot();
$app['load']('routes');

$app->run();
