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

/**
 * Logger Handlers
 */

namespace Cloud\Monolog\Provider;

use Cloud\Monolog\Formatter\LineFormatter;

use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\MonologServiceProvider;

class LogServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->register(new MonologServiceProvider());

        $app['monolog'] = $app->share($app->extend('monolog', function($monolog, $app) {
            $app['monolog.name'] = 'cloud';
            return $monolog;
        }));

        $formatter = new LineFormatter();

        // Setup handler for logging to logEntries.com
        $app['monolog.handler.logentries'] = function() use ($app, $formatter) {
            $token = $app['config']['logentries']['token'];
            $handler = new LogEntriesHandler($token, Logger::DEBUG);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // Setup handler for logging to the cli 
        $app['monolog.handler.debug'] = function() use ($app, $formatter) {
            $handler = new StreamHandler(fopen('php://stderr', 'w'), Logger::DEBUG);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // Define a factory to allow components
        // to setup their own namespaced loggers
        $app['monolog.factory'] = $app->protect(function($name) use ($app) {
            $logger = new Logger($name);
            $logger->pushHandler($app['monolog.handler.logentries']);
            $logger->pushHandler($app['monolog.handler.debug']);
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

    public function boot(Application $app)
    {
    }
}
