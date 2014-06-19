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

use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Monolog\Handler\StreamHandler;

$logDir = ($app['env'] == 'development') ? 'data/logs/' : 'log/';

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => $logDir . 'development.log',
    'monolog.streamHandler' => function() use ($app) {
        return new StreamHandler($app['monolog.logfile']);
    },
]);

$app['monolog.factory'] = $app->protect(function ($name) use ($app) {
    $log = new $app['monolog.logger.class']($name);
    $log->pushHandler($app['monolog.handler']);

    return $log;
});

$app['monolog.dev'] = $app->share(function() use ($app) {
    return $app['monolog.factory']('dev');
});

$app['monolog.app'] = $app->share(function() use ($app) {
    return $app['monolog.factory']('app');
});

$app['monolog']->pushHandler($app['monolog.streamHandler']);
$app['monolog.dev']->pushHandler($app['monolog.streamHandler']);
$app['monolog.app']->pushHandler($app['monolog.streamHandler']);

