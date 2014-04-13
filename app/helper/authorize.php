<?php
/**
 * Route middleware to authorize a request
 *
 * $app->get('/protected', $app->authorize(), function () use ($app) {
 *    $app->json('yay, access granted...');
 * });
 */

$authorize = function ($scope = null) use ($app) {
    return function () use ($app, $scope) {
        if (!$app->session->isLoggedIn()) {
            $app->json(401, [
                'error' => 'invalid_client',
                'error_description' => 'You are not authorized to access this resource',
            ]);

            $app->stop();
        }
    };
};

$app->authorize = $app->container->protect($authorize);
