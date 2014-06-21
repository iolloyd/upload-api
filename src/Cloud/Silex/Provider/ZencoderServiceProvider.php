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

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Collections\Criteria;

use Services_Zencoder;

class ZencoderServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['zencoder'] = $app->share(function (Application $app) {
            $token = isset($app['zencoder.token']) 
                ? $app['zencoder.token'] 
                : $app['config']['zencoder']['token'];

            return new Services_Zencoder($token);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}



