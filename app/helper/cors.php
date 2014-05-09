<?php
/**
 * Send CORS headers for XHR requests
 */

$app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
$app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN');
$app->response->headers->set('Access-Control-Allow-Credentials', 'true');
$app->response->headers->set('Access-Control-Max-Age', '604800');
$app->response->headers->set('Vary', 'Origin, Access-Control-Request-Headers, Access-Control-Request-Method, X-XSRF-TOKEN');

$app->configureMode('development', function () use ($app) {
    $app->response->headers->set('Access-Control-Allow-Origin', $app->request->headers->get('Origin'));
});

/**
 * Always stop and send empty response for OPTIONS requests
 */
$app->options('.*', function () use ($app) {
    $app->status(204);
    $app->stop();
});
