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
        'db.password' => 'root',
    ]);

    // app
    $app->config([
        'app.baseurl' => $app->request->getUrl(),
    ]);

    // amazon
    $app->config([
        's3.bucket' => 'cldsys-dev',
        's3.key'    => 'AKIAJFJWKRRF6DGEPXCA',
        's3.secret' => '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',
        's3.region' => 'us-west-2',
    ]);
});
