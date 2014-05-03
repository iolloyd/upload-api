<?php
/**
 * Doctrine Utilities
 */

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

/**
 * Factory
 */
$emFactory = function () use ($app)
{
    $paths = [
        'src/Cloud/Model/',
    ];

    $params = [
        'driver'   => $app->config('db.driver'),
        'host'     => $app->config('db.host'),
        'dbname'   => $app->config('db.dbname'),
        'user'     => $app->config('db.user'),
        'password' => $app->config('db.password'),

        'path'     => $app->config('db.path'), // optional for sqlite

        'charset'  => 'utf8',
    ];

    /*
     * Inside the Setup methods several assumptions are made:
     *
     *  - if debug is true
     *    - caching is done in memory with the ArrayCache
     *    - Proxy objects are recreated on every request
     *
     *  - if debug is false
     *    - check for caches in the order APC, Xcache, Memcache (127.0.0.1:11211),
     *      Redis (127.0.0.1:6379) unless $cache is passed as fourth argument
     *    - proxy classes have to be explicitly created through the command line
     *
     *  - if third argument $proxyDir is not set, use the systems temporary
     *    directory
     */
    $config = Setup::createAnnotationMetadataConfiguration(
        $paths,
        $app->config('debug')
    );
    $config->setNamingStrategy(new UnderscoreNamingStrategy());
    $config->addEntityNamespace('cx', 'Cloud\Model');

    $em = EntityManager::create(
        $params,
        $config
    );

    return $em;
};

/**
 * Get the Doctrine entity manager as `$app->em`
 */
$app->container->singleton('em', $emFactory);
