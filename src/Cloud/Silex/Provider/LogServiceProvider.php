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
use Monolog\Handler\GroupHandler;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Silex\ServiceProviderInterface;

class LogServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->register(new MonologServiceProvider(), [
            'monolog.name' => 'cloudxxx',
        ]);

        $formatter = new LineFormatter();

        // default handler
        $app['monolog.handler'] = function () use ($app) {
            return new GroupHandler([
                $app['monolog.handler.logentries'],
            ]);
        };

        // logentries handler

        $app['monolog.handler.logentries'] = function() use ($app, $formatter) {
            $token = $app['config']['logentries']['token'];
            $handler = new LogEntriesHandler($token, Logger::INFO);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // debug to cli handler

        $app['monolog.handler.debug'] = function() use ($app, $formatter) {
            $handler = new StreamHandler(fopen('php://stderr', 'w'), Logger::DEBUG);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // define a factory to allow setup of namespaced loggers or 'channels'

        $app['monolog.factory'] = $app->protect(function($name) use ($app)
        {
            $logger = new $app['monolog.logger.class']($name);
            $logger->pushHandler($app['monolog.handler']);

            if (/* $app['debug'] && */ isset($app['monolog.handler.debug'])) {
                $logger->pushHandler($app['monolog.handler.debug']);
            }

            $logger->pushProcessor(function($record) use ($app) {
                $record['extra']['user']    = $app['user'] ? $app['user']->getId() : 0;
                $record['extra']['company'] = $app['company'] ? $app['company']->getId() : 0;
                $record['extra']['host']    = gethostname();

                foreach ($record['context'] as $k => $v) {
                    $record['extra'][$k] = $v;
                }

                return $record;
            });

            return $logger;
        });
    }

    public function boot(Application $app)
    {
    }
}
