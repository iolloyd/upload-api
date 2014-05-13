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

// Init autoloading
require 'autoload.php';

/*
 * Silex Application
 */

$app = new Cloud\Silex\Application();
$app['route_class'] = 'Cloud\Silex\Route';

// env
$app['env'] = 'development';
$app['debug'] = ($app['env'] == 'development');

// config
$app->register(new Herrera\Wise\WiseServiceProvider(), [
    'wise.path' => 'app/config/',
]);
$app['config'] = $app['wise']->load($app['env'] . '.ini');

// db
$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => $app['config']['db.options']

]);

$app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), [
    'orm.em.options' => [
        'mappings' => [
            [
                'type'      => 'annotation',
                'namespace' => 'Cloud\Model',
                'path'      => 'src/Cloud/Model/',
            ],
        ],
    ],
]);

$app['em'] = $app['orm.em'];

// providers
$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'login' => [
            'pattern' => '^/login$',
        ],
        'default' => [
            'pattern' => '^/',
            'anonymous' => true,
            'users' => array(
                // raw password is foo
                'user' => array('ROLE_USER', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
                'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            ),
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

// loader
$app->register(new Cloud\Silex\Loader(), [
    'loader.path' => 'app/',
    'loader.extensions' => [
        'php',
    ],
]);
$app['load']('helper');

// run
$app->boot();
$app['load']('routes');

$app->run();
