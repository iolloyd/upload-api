<?php

namespace Cloud\Slim\Middleware;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Gedmo\Timestampable\TimestampableListener;
use Slim\Middleware;

/**
 * Doctrine Integration
 */
class Doctrine extends Middleware
{
    /**
     * Init Middleware
     */
    public function call()
    {
        $app = $this->app;

        // $app->em
        $app->container->singleton('em', [$this, 'em']);

        // $app->find()
        $app->find = $app->container->protect(function () {
            return $this->find(func_num_args() ? func_get_args() : null);
        });

        $this->next->call();
    }

    /**
     * Get the Doctrine entity manager as `$app->em`
     *
     * @return EntityManager
     */
    public function em()
    {
        $app = $this->app;

        $paths = [
            'src/Cloud/Model/',
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

        $defaultDriver = $config->getMetadataDriverImpl();
        $defaultReader = $defaultDriver->getReader();

        $driverChain = new MappingDriverChain();
        $driverChain->setDefaultDriver($defaultDriver);
        $config->setMetadataDriverImpl($driverChain);

        \Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain, $defaultReader);

        $params = $this->getConnectionParams();
        $em = EntityManager::create($params, $config);

        $timestampableListener = new TimestampableListener();
        $timestampableListener->setAnnotationReader($defaultReader);

        $evm = $em->getEventManager();
        $evm->addEventSubscriber($timestampableListener);

        return $em;
    }

    /**
     * Parameters for database connection and configuration
     */
    protected function getConnectionParams()
    {
        $app = $this->app;

        $connection = [
            'driver'   => $app->config('db.driver'),
            'host'     => $app->config('db.host'),
            'dbname'   => $app->config('db.dbname'),
            'user'     => $app->config('db.user'),
            'password' => $app->config('db.password'),

            'path'     => $app->config('db.path'), // optional for sqlite

            'charset'  => 'utf8',
        ];

        return $connection;
    }

    /**
     * Route middleware: load models from route params
     *
     * TODO: docs
     */
    public function find(array $models = null)
    {
        $app = $this->app;

        return function ($route) use ($app, $models) {
            $params = array_keys($route->getParams());

            if (!$models) {
                $models = $params;
            } elseif (count($models) > count($params)) {
                throw new InvalidArgumentException(
                    '$app->find(): number of models exceeds number of route params'
                );
            }

            for ($i = 0; $i < count($models); $i++) {
                $param = $params[$i];
                $model = $models[$i];

                // skip params where $model is null

                if ($model === null) {
                    continue;
                }

                // fetch

                $model = preg_replace('/^(cx:|Cloud\\\\Model\\\\)/', '', $model);
                $repository = $app->em->getRepository('cx:' . $model);

                $id = $route->getParam($param);
                $object = $repository->find($id);

                // not found

                if (!$object) {
                    $app->log->notice(sprintf(
                        '[$app->find()] model %s#%s for param :%s not found in database',
                        $repository->getClassName(), $id, $param
                    ));

                    return $app->notFound();
                }

                // authorize user belongs to company
                // TODO

                // inject result

                $route->setParam($param, $object);
            }
        };
    }

}

