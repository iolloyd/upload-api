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

use Monolog\Formatter\JsonFormatter;

use Monolog\Handler\FingersCrossedHandler;
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
        $app->register(new MonologServiceProvider(), [
            'monolog.name' => 'cloudxxx',
        ]);
        $app['monolog.logfile'] = 'data/logs/'.$app['env'].'.log';
        $app['monolog.fingerscrossed'] = true;
        $app['monolog.fingerscrossed.level'] = Logger::WARNING;
        $app['monolog.fingerscrossed.handler'] = function() use ($app){
            return new StreamHandler($app['monolog.logfile']);
        };

        $app['monolog.rotatingfile'] = true;
        $app['monolog.rotatingfile.maxfiles'] = $app['debug'] ? 2 : 7;
        $app['monolog.handler'] = function() use ($app){

            $level = static::translateLevel($app['monolog.level']);
            if ($app['debug'] == true) {
                $handler = new StreamHandler($app['monolog.logfile'], $level);
                $handler->setFormatter(new JsonFormatter());
                return $handler;
            }

            if ($app['monolog.rotatingfile'])
                $app['monolog.fingerscrossed.handler'] = new RotatingFileHandler(
                    $app['monolog.logfile'],
                    $app['monolog.rotatingfile.maxfiles'],
                    // Depending on environment and type of thing logged
                    $level
                );

            $activationLevel = static::translateLevel($app['monolog.fingerscrossed.level']);
            if ($app['monolog.fingerscrossed']) {
                $handler = new FingersCrossedHandler(
                    $app['monolog.fingerscrossed.handler'],
                    $activationLevel
                );
            } else {
                $handler = $app['monolog.fingerscrossed.handler'];
            }

            $handler->setFormatter(new JsonFormatter());

            return $handler;
        };

    }

    public function boot(Application $app)
    {
    }
}
