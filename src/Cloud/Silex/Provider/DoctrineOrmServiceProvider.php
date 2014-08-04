<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace Cloud\Silex\Provider;

use Cloud\Doctrine\ManagerRegistry;
use Silex\Application;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider as BaseDoctrineOrmServiceProvider;

/**
 * Doctrine ORM service extensions
 *
 *  - orm.manager_registry:  exposes available managers and connections from
 *                             the Application to other components
 *
 */
class DoctrineOrmServiceProvider extends BaseDoctrineOrmServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        parent::register($app);

        $app['orm.manager_registry'] = $app->share(function ($app)
        {
            $app['orm.ems.options.initializer']();

            $connectionNames = array_keys($app['dbs.options']);
            $managerNames = array_keys($app['orm.ems.options']);

            return new ManagerRegistry(
                $app,
                'silex.doctrine.orm',
                'dbs',
                'orm.ems',
                $connectionNames,
                $managerNames,
                $app['dbs.default'],
                $app['orm.ems.default']
            );
        });
    }
}
