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

use Cloud\Monolog\Formatter\LineFormatter;
use Cloud\Monolog\Handler\LogEntriesHandler;
use Monolog\Logger;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\MonologServiceProvider;

class LogServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app->register(new MonologServiceProvider(), [
            'monolog.name' => 'cloudxxx',
        ]);


        // default handler
        $app['monolog.handler'] = function () use ($app) {
            return new GroupHandler([
                $app['monolog.handler.logentries'],
            ]);
        };

        // handler to send logs to logentries.com
        $app['monolog.handler.logentries'] = function() use ($app)
        {
            $token = $app['config']['logentries']['token'];
            $handler = new LogEntriesHandler($token, Logger::WARNING, true);
            $handler->setFormatter(new LineFormatter());

            return $handler;
        };

        // handler to send logs to local terminal on stderr
        $app['monolog.handler.local'] = function() use ($app)
        {
            $handler = new StreamHandler(fopen('php://stderr', 'w'), Logger::DEBUG, true);
            $handler->setFormatter(new LineFormatter());
            return $handler;
        };

        $app['monolog']->pushHandler($app['monolog.handler.logentries']);

        // define a factory to allow setup of namespaced loggers or 'channels'
        $app['monolog.factory'] = $app->protect(function($name) use ($app)
        {
            /** @var $logger \Monolog\Logger */
            $logger = new $app['monolog.logger.class']($name);
            $logger->pushHandler($app['monolog.handler']);

            if ($app['debug'] && isset($app['monolog.handler.local'])) {
                $logger->pushHandler($app['monolog.handler.local']);
            }

            $logger->pushProcessor(function($record) use ($app) {
                $record['extra']['user']    = empty($app['user']) ? 0 : $app['user']->getId();
                $record['extra']['company'] = empty($app['user']) ? 0 : $app['user']->getCompany()->getId();
                $record['extra']['host']    = gethostname();
                foreach ($record['context'] as $k => $v) {
                    $record['extra'][$k] = $v;
                }

                return $record;
            });

            return $logger;
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
