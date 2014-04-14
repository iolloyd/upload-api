<?php
/**
 * Initialize AWS Clients
 */

$app->container->singleton('s3', function () use ($app) {
    return \Aws\S3\S3Client::factory([
        'key'    => $app->config('s3.key'),
        'secret' => $app->config('s3.secret'),
        'region' => $app->config('s3.region'),
    ]);
});
