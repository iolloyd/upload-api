<?php

use Aws\S3\S3Client;

/**
 * Get the S3 Client instance
 */
$app->container->singleton('s3', function () use ($app) {
    return S3Client::factory([
        'key'    => $app->config('s3.key'),
        'secret' => $app->config('s3.secret'),
        'region' => null, // no region for S3
    ]);
});
