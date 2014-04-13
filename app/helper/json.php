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
        $app->response->setStatus($statusOrData);
        $statusOrData = $data;
    }

    $app->response->headers->set('Content-Type', 'application/json');
    $app->response->setBody(json_encode($statusOrData));
};

$app->json = $app->container->protect($json);
