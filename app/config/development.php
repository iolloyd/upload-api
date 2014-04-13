<?php

$app->configureMode('development', function () use ($app) {
    // slim
    $app->config([
        'debug'       => true,
        'log.enabled' => false,
    ]);

    // db
    $app->config([
        'db.dsn'      => 'mysql:host=localhost;dbname=cloudxxx',
        'db.username' => 'root',
        'db.password' => '',
    ]);

    // app
    $app->config([
        'app.baseurl' => $app->request->getUrl(),
    ]);
});
