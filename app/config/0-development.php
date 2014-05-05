<?php

$app->configureMode('development', function () use ($app) {
    // slim
    $app->config([
        'debug'       => true,
        'log.enabled' => false,
        'log.level'   => \Slim\Log::INFO,
    ]);

    // db
    $app->config([
        'db.driver'   => 'pdo_mysql',
        'db.host'     => 'localhost',
        'db.dbname'   => 'cloudxxx',
        'db.user'     => 'root',
        'db.password' => 'root',
    ]);

    // amazon
    $app->config([
        's3.bucket' => 'cldsys-dev',
        's3.key'    => 'AKIAJFJWKRRF6DGEPXCA',
        's3.secret' => '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',
    ]);

    // app
    $app->config([
        'baseurl' => $app->request->getUrl(),
    ]);
});
