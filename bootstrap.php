<?php
/*
 * Silex Application Bootstrap
 */

$app = new Cloud\Silex\Application();
$app['route_class'] = 'Cloud\Silex\Route';

// env
$app['env'] = 'development';
$app['debug'] = ($app['env'] == 'development');

// config
$configs = [
    $app['env'] . '.ini',
    'local.ini',
];
$app->register(new Herrera\Wise\WiseServiceProvider(), [
    'wise.path' => 'app/config/',
]);
$app['config'] = array_reduce($configs, function (array $data, $file) use ($app) {
    try { return array_replace_recursive($data, $app['wise']->load($file)); }
    catch (Exception $e) { return $data; }
}, []);

// db
$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => $app['config']['db.options'],
]);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'Cloud\Doctrine\Annotation', 'src/'
);
$app->extend('dbs.event_manager', function ($managers, $app) {
    foreach ($app['dbs.options'] as $name => $options) {
        $managers[$name]->addEventSubscriber(new Cloud\Doctrine\TimestampEventSubscriber());
        $managers[$name]->addEventSubscriber(new Cloud\Doctrine\SecurityEventSubscriber($app));
    }
    return $managers;
});
$app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider(), [
    'orm.em.options' => [
        'mappings' => [
            [
                'alias'     => 'cx',
                'type'      => 'annotation',
                'namespace' => 'Cloud\Model',
                'path'      => 'src/Cloud/Model/',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ],
]);
$app->extend('orm.ems.config', function ($configs, $app) {
    foreach ($app['orm.ems.options'] as $name => $options) {
        $configs[$name]->setNamingStrategy(new Doctrine\ORM\Mapping\UnderscoreNamingStrategy());
    }
    return $configs;
});
$app['em'] = $app['orm.em'];

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

// loader
$app->register(new Cloud\Silex\Loader(), [
    'loader.path' => 'app/',
    'loader.extensions' => [
        'php',
    ],
]);

$app['load']('helper');
