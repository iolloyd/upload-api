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

		$formatter = new LineFormatter();

		// Setup handler for logging to logEntries.com
        $app['monolog.handler'] = function() use ($app, $formatter) {
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
			$logger->pushHandler($app['monolog.handler']);
			$logger->pushHandler($app['monolog.handler.debug']);
			$logger->pushProcessor(function($record) {
				$record['extra']['user'] = 123;//$app['user'];

				return $record;
			});

			return $logger;
		});

		$app['logger.debug'] = $app['monolog.factory']('deebug');
    }

    public function boot(Application $app)
    {
    }
}
