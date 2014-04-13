<?php
/**
 * Initialize RedBean Database
 */

R::setup(
    $app->config('db.dsn'),
    $app->config('db.username'),
    $app->config('db.password')
);

R::freeze(true);

$app->configureMode('development', function () use ($app) {
    R::freeze(false);

    $debugLogger = new \Cloud\RedBean\Logger\Slim($app);
    R::getDatabaseAdapter()->getDatabase()->setDebugMode(true, $debugLogger);
});
