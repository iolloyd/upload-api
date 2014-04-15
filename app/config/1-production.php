<?php

$app->configureMode('production', function () use ($app) {
    // app
    $app->config([
        'app.baseurl' => 'https://api.cloud.xxx/v1',
    ]);
});
