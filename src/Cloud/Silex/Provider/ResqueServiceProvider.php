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

use Cloud\Resque\Job;
use Cloud\Resque\Resque;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ResqueServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['resque.default_options'] = [
            'plugins' => [
                'history',
                'status',
                'ruby_names',
            ],
        ];

        $app['resque.options'] = [];

        // services

        $app['resque.logger'] = $app['logger'];
        $app['resque.dispatcher'] = $app['dispatcher'];

        /**
         * Get the  Resque manager instance
         */
        $app['resque'] = $app->share(function ($app) {
            $app['resque.options'] = array_replace(
                $app['resque.default_options'],
                $app['resque.options']
            );

            $resque = new Resque($app['resque.logger'], $app['resque.dispatcher']);

            // FIXME: hack
            $resque->redisHost = $app['config']['redis']['host'];

            return $resque;
        });

        /**
         * Get the status for a given job
         *
         * @param string|Job $job  job to track or its UUID
         *
         * @return \Cloud\Resque\Plugin\Status\Job\Status
         */
        $app['resque.status'] = $app->protect(function ($job) use ($app) {
            if ($job instanceof Job) {
                $job = $job->getParameter('uuid');
            }

            return $app['resque.plugin.status']->load($job);
        });

        // plugins

        $app['resque.plugin._register'] = $app->protect(function ($name, $class) use ($app) {
            return $app['resque.plugin.' . $name] = $app->share(function ($app) use ($name, $class) {
                $options = isset($app['resque.options']['plugin'][$name])
                         ? $app['resque.options']['plugin'][$name]
                         : [];

                return new $class($app['resque'], $options);
            });
        });

        $app['resque.plugin._register']('status', 'Cloud\Resque\Plugin\Status\StatusPlugin');
        $app['resque.plugin._register']('ruby_names', 'Cloud\Resque\Plugin\RubyNames\RubyNamesPlugin');
        $app['resque.plugin._register']('history', 'Cloud\Resque\Plugin\History\HistoryPlugin');
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        $dispatcher = $app['resque']->getEventDispatcher();

        foreach ($app['resque.options']['plugins'] as $name) {
            $dispatcher->addSubscriber($app['resque.plugin.' . $name]);
        }
    }
}
