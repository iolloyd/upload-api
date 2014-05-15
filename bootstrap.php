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

// loader
$app->register(new Cloud\Silex\Loader(), [
    'loader.path' => 'app/',
    'loader.extensions' => [
        'php',
    ],
]);

$app['load']('helper');
