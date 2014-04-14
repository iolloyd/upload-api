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
    $result = $app->session->login([
        'email' => $app->request->post('email'),
        'password' => $app->request->post('password'),
    ]);

    $app->json($app->request->post());
    $app->stop();

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
 * Test Request
 */
$app->get('/protected', $app->authorize(), function () use ($app)
{
    $app->json('yay, access granted...');
});

/**
 * Render standard response payload for session requests.
 * Invoked via `$app->pass()` from the method specific function.
 */
$app->any('/session', function () use ($app)
{
    $json = [
        'endpoints' => [
            'default' => $app->config('app.baseurl'),
        ],
        'config' => [
            'mode' => $app->config('mode'),
        ],
    ];

    if ($app->session->isLoggedIn()) {
        $json['user'] = $app->user;
        $json['account'] = $app->account;
    }

    $app->json($json);
});
