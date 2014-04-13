<?php
/**
 * Send CORS headers for XHR requests
 */

$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
$app->response->headers->set('Access-Control-Allow-Credentials', 'true');
$app->response->headers->set('Access-Control-Max-Age', '3600');

$app->configureMode('development', function () use ($app) {
    $app->response->headers->set('Access-Control-Allow-Origin', '*');
});

