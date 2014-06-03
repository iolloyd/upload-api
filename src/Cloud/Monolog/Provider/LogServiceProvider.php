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
            $level = self::translateLevel($app['monolog.level']);
            if ($app['debug'] == true) {
                $handler = new StreamHandler($app['monolog.logfile'], $level);
                $handler->setFormatter(new LineFormatter());
                return $handler;
            }

            if ($app['monolog.rotatingfile'])
                $app['monolog.fingerscrossed.handler'] = new RotatingFileHandler(
                    $app['monolog.logfile'],
                    $app['monolog.rotatingfile.maxfiles'],
                    $level
                );

            $activationLevel = self::translateLevel($app['monolog.fingerscrossed.level']);
            if ($app['monolog.fingerscrossed']) {
                $handler = new FingersCrossedHandler(
                    $app['monolog.fingerscrossed.handler'],
                    $activationLevel
                );
            } else {
                $handler = $app['monolog.fingerscrossed.handler'];
            }

            $handler->setFormatter(new LineFormatter());

            return $handler;
        };

        $token = $app['config']['logentries']['token'];
        $app['monolog']->pushHandler(
            new LogEntriesHandler($token)
        );
    }

    public static function translateLevel($name)
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }
    public function boot(Application $app)
    {
    }
}
