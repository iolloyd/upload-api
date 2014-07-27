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

use ReflectionClass;
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
        $app['zencoder'] = $app->share(function () use ($app) {
            $zencoder = new Services_Zencoder($app['config']['zencoder']['api_key']);

            $this->monkeyPatchZencoderSsl($zencoder);

            return $zencoder;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * Fixes the Zencoder client CAINFO settings to avoid SSL
     * verification errors
     *
     *   Services_Zencoder_HttpException:
     *   SSL certificate problem: unable to get local issuer certificate
     *
     * To avoid the error we point the client to the SSL data included
     * with Guzzle.
     *
     * @return void
     */
    protected function monkeyPatchZencoderSsl(Services_Zencoder $zencoder)
    {
        $reflClass = new ReflectionClass($zencoder);
        $reflProperty = $reflClass->getProperty('http');
        $reflProperty->setAccessible(true);

        $http = $reflProperty->getValue($zencoder);

        $httpReflClass = new ReflectionClass($http);
        $httpReflProperty = $httpReflClass->getProperty('curlopts');
        $httpReflProperty->setAccessible(true);

        $curlopts = $httpReflProperty->getValue($http);

        if (!isset($curlopts[CURLOPT_CAINFO])) {
            $curlopts[CURLOPT_CAINFO] = realpath('vendor/guzzlehttp/guzzle/src/cacert.pem');
            $httpReflProperty->setValue($http, $curlopts);
        }
    }
}
