<?php

\Symfony\Component\Debug\ErrorHandler::register();
\Symfony\Component\Debug\ExceptionHandler::register();

/*
 * Silex Application Bootstrap
 */

$app = new Cloud\Silex\Application();
$app['route_class'] = 'Cloud\Silex\Route';

// env
$app['env'] = getenv('CLOUD_ENV') ?: 'development';
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

// log
$app->register(new Cloud\Silex\Provider\LogServiceProvider());

// opsworks
if ($app['env'] != 'development') {
    $app->register(new \Cloud\Silex\Provider\OpsWorksServiceProvider());
    $app['config'] = array_replace_recursive($app['config'], $app['opsworks.config']);
}

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
        $managers[$name]->addEventSubscriber(new Cloud\Doctrine\SecurityEventSubscriber($app));
    }
    return $managers;
});

// Doctrine ORM setup
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
    'orm.default_cache' => $app['debug']
            ? 'array'
            : 'apc',

    'orm.auto_generate_proxies' => !$app['debug'],
    'orm.proxies_dir' => 'data/cache/doctrine/proxies',
]);

$app->extend('orm.ems.config', function ($configs, $app) {
    foreach ($app['orm.ems.options'] as $name => $options) {
        $configs[$name]->setNamingStrategy(new Doctrine\ORM\Mapping\UnderscoreNamingStrategy());
        $configs[$name]->setClassMetadataFactoryName('Cloud\Doctrine\ORM\Mapping\ClassMetadataFactory');
        $configs[$name]->addFilter('security', 'Cloud\Doctrine\ORM\Query\Filter\SecurityFilter');
    }
    return $configs;
});

$app['em'] = $app['orm.em'];

// middleware
$app->register(new Aws\Silex\AwsServiceProvider(), [
    'aws.config' => $app['config']['aws'],
]);

$app->register(new Cloud\Monolog\Provider\LogServiceProvider());
$app->register(new Cloud\Silex\Provider\ZencoderServiceProvider());
$app->register(new Cloud\Silex\Provider\ResqueServiceProvider());

// loader
$app->register(new Cloud\Silex\Loader(), [
    'loader.path' => 'app/',
    'loader.extensions' => [
        'php',
    ],
]);

$app['load']('helper');
