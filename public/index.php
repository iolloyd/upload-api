<?php

use Cloud\Silex\Provider\CorsHeadersServiceProvider;
use Cloud\Silex\Provider\DoctrinePaginatorServiceProvider;
use Cloud\Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;

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

// providers
$app->register(new SecurityServiceProvider(), [
    'security.logger' => function ($app) { return $app['monolog']('security'); },
]);
$app->register(new SessionServiceProvider(), [
    'session.storage.options' => [
        'name'                    => 'CLOUD',
        'hash_function'           => 'sha256',
        'hash_bits_per_character' => 6,
        'cookie_lifetime'         => $app['debug'] ? 0 : 3600*24*6,
        'cookie_secure'           => !$app['debug'],
        'cookie_httponly'         => true,
    ],
]);
$app->register(new CorsHeadersServiceProvider(), [
    'cors.options' => [
        'allow_credentials' => true,
        'allow_origin'      => $app['debug'] ? null : ($app['env'] == 'staging' ? 'https://app.cldstaging.net' : 'https://beta.cloud.xxx'),
        'max_age'           => 604800,
    ],
]);
$app->register(new UrlGeneratorServiceProvider());
$app->register(new DoctrinePaginatorServiceProvider());

// json request parser
$app->before(function ($request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : []);
    }
});

// run
$app->boot();
$app['load']('routes');

$app->run();
