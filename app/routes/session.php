<?php

/**
 * Get state of session and global config
 */
$app->get('/session', function () use ($app)
{
    if (!$app->session->isLoggedIn()) {
        $app->status(401);
    }

    $app->pass();
});

/**
 * Log In
 */
$app->post('/session', function () use ($app)
{
    $result = $app->session->login(
        ['email' => $app->param('email')],
        $app->param('password')
    );

    if (!$result) {
        $app->jsonError(400, 'invalid_grant', 'Invalid username or password');
    }

    $app->pass();
});

/**
 * Log Out
 */
$app->delete('/session', function () use ($app)
{
    $app->session->logout();
    $app->pass();
});

/**
 * Render standard response payload for session requests.
 * Invoked via `$app->pass()` from the method specific function.
 */
$app->any('/session', function () use ($app)
{
    $json = [
        'endpoints' => [
            'default' => $app->config('baseurl'),
        ],
        'config' => [
            'mode' => $app->config('mode'),
        ],
    ];

    if ($app->session->isLoggedIn()) {
        $json['user'] = $app->session->user();
        $json['company'] = $app->session->company();
    }

    $app->json($json);
});
