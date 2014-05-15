<?php

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

// raw password is foo
$userToken = '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==';

// providers
$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'login' => [
            'pattern' => '^/login$',
        ],
        'default' => [
            'pattern' => '^/',
            'security' => $app['debug'] ? false : true,
            'anonymous' => true,
            'users' => [ 
                'user' => ['ROLE_USER', $userToken],
                'admin' => ['ROLE_ADMIN', $userToken],
            ],
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


