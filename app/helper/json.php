<?php
/**
 * Route middleware to authorize a request
 *
 * $app->get('/protected', $app->authorize(), function () use ($app) {
 *    $app->json('yay, access granted...');
 * });
 */

$json = function ($statusOrData, $data = null) use ($app)
{
    if ($data) {
        $app->response->status($statusOrData);
        $statusOrData = $data;
    }

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->body(json_encode($statusOrData));
};


$jsonError = function ($status, $error = null, $errorDescription = null) use ($app)
{
    $app->json($status, [
        'error' => $error,
        'error_description' => $errorDescription,
    ]);

    $app->stop();
};

$app->json = $app->container->protect($json);
$app->jsonError = $app->container->protect($jsonError);
