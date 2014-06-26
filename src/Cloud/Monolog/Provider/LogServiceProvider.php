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

namespace Cloud\Monolog\Provider;

use Cloud\Monolog\Formatter\LineFormatter;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\MonologServiceProvider;

class LogServiceProvider extends MonologServiceProvider
{
    public function register(Application $app)
    {
        parent::register($app);

        $app['monolog'] = $app->share($app->extend('monolog', function($monolog, $app) {
            $app['monolog.name'] = 'cloud';
            return $monolog;
        }));

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
            $handler = new LogEntriesHandler($token, Logger::DEBUG);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // debug to cli handler
        $app['monolog.handler.debug'] = function() use ($app, $formatter) {
            $handler = new StreamHandler(fopen('php://stderr', 'w'), Logger::DEBUG);
            $handler->setFormatter($formatter);

            return $handler;
        };

        // define a factory to allow components
        // to setup their own namespaced loggers
        $app['monolog.factory'] = $app->protect(function($name) use ($app)
        {
            $logger = new $app['monolog.logger.class']($name);

            $logger->pushHandler($app['monolog.handler']);

            if ($app['debug'] && isset($app['monolog.handler.debug'])) {
                $logger->pushHandler($app['monolog.handler.debug']);
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
}
