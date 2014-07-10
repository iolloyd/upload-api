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
            'monolog.name'    => 'cloudxxx',
            'monolog.logfile' => 'data/logs/development.log',
            'monolog.level'   => Logger::DEBUG,
        ]);

        // Handler for logentries
        $app['monolog.handler.logentries'] = function() use ($app)
        {
            $token = $app['config']['logentries']['token'];
            $handler = new LogEntriesHandler($token);
            $handler->setFormatter(new LineFormatter());
            return $handler;
        };

        // Handler for stderr
        $app['monolog.handler.debug'] = function() use ($app)
        {
            $handler = new StreamHandler(fopen('php://stderr', 'w'), Logger::WARNING);
            $handler->setFormatter(new LineFormatter());
            return $handler;
        };

        // Processor for all logging channels
        $app['monolog.processor'] = $app->protect(function ($record) use ($app)
        {
            $record['extra']['user']    = empty($app['user']) ? 0 : $app['user']->getId();
            $record['extra']['company'] = empty($app['user']) ? 0 : $app['user']->getCompany()->getId();
            $record['extra']['host']    = gethostname();
            foreach ($record['context'] as $k => $v) {
                $record['extra'][$k] = $v;
            }

            return $record;
        });

        $app['monolog']->pushHandler($app['monolog.handler.logentries']);
        $app['monolog']->pushHandler($app['monolog.handler.debug']);
        $app['monolog']->pushProcessor($app['monolog.processor']);

        // Factory for component level logging channels
        $app['monolog.factory'] = $app->protect(function($name) use ($app)
        {
            $logger = new $app['monolog.logger.class']($name);
            $logger->pushHandler($app['monolog.handler.logentries']);

            if ($app['debug'] && isset($app['monolog.handler.debug'])) {
                $logger->pushHandler($app['monolog.handler.debug']);
            }

            $logger->pushProcessor($app['monolog.processor']);

            return $logger;
        });
    }

    public function boot(Application $app)
    {
    }
}
