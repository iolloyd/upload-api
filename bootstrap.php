<?php
/*
 * Silex Application Bootstrap
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Provider\Silex\WhoopsServiceProvider;


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
    'wise.options' => [
        'parameters' => [
            'env' => $_SERVER,
        ],
    ],
]);
$app['config'] = array_reduce($configs, function (array $data, $file) use ($app) {
    try { return array_replace_recursive($data, $app['wise']->load($file)); }
    catch (Exception $e) { return $data; }
}, []);

// db
$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => $app['config']['db'],
]);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'Cloud\Doctrine\Annotation', 'src/'
);

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'JMS\Serializer\Annotation', 'vendor/jms/serializer/src'
);

$app->extend('dbs.event_manager', function ($managers, $app) {
    foreach ($app['dbs.options'] as $name => $options) {
        $managers[$name]->addEventSubscriber(new Cloud\Doctrine\TimestampEventSubscriber());
        $managers[$name]->addEventSubscriber(new Cloud\Doctrine\IdentityEventSubscriber($app));
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

// aws
$app->register(new Aws\Silex\AwsServiceProvider(), [
    'aws.config' => $app['config']['aws'],
]);

$app->register(new \Cloud\Monolog\Provider\LogServiceProvider(), [
]);

$app['logger.name'] = 'cloudxxx';

$app->register(new \Cloud\Silex\Loader(), [
    'loader.path' => 'app/',
    'loader.extensions' => [
        'php',
    ],
]);

$app['load']('helper');

if ( $app['debug'] ) {
    $app->register(new WhoopsServiceProvider);
}

if ($app['debug']) {
    $logger = new \Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);

    $app->after(function(Request $request, Response $response) use ($app, $logger) {
        foreach ( $logger->queries as $query ) {
            $app['monolog']->debug($query['sql'], [
                'params' => $query['params'],
                'types' => $query['types']
            ]);
        }
    });
}

$app->finish(function(Request $request, Response $response) use ($app) {
    $app['logger']->addInfo($request);
    $app['logger']->addInfo($response);
});

