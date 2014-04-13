<?php

/**
 * Get state of session and global config
 */
$app->get('/session', function () use ($app)
{
    $json = [
        'endpoints' => [
            'default' => $app->config('app.baseurl'),
        ],
        'config' => [
            'mode' => $app->config('mode'),
        ],
    ];

    if (!$app->session->isLoggedIn()) {
        $app->json(401, $json);
    } else {
        $json['user'] = [

        ];

        $json['account'] = [

        ];

        $app->json($json);
    }
});

/**
 * Test Request
 */
$app->get('/protected', $app->authorize(), function () use ($app)
{
    $app->json('yay, access granted...');
});
