<?php

$app->configureMode('production', function () use ($app) {
    // slim
    $app->config([
        'debug'       => false,
        'log.enabled' => true,
        'log.level'   => \Slim\Log::WARN,
    ]);

    // db
    $app->config([
        'db.driver'   => 'pdo_mysql',
        'db.host'     => 'localhost',
        'db.dbname'   => 'cloudxxx',
        'db.user'     => 'www-data',
        'db.password' => null,
    ]);

    // amazon
    $app->config([
        's3.bucket' => 'cldsys-prod',
        's3.key'    => null,
        's3.secret' => null,
    ]);

    // app
    $app->config([
        'baseurl' => 'https://api.cloud.xxx/v1',
    ]);
});
