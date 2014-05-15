<?php

/**
 * @package  cloudxxx-api (http://www.cloud.xxx)
 *
 * @author    ReallyUseful <info@ruseful.com>
 * @copyright 2014 Really Useful Limited
 * @license   Proprietary code. Usage restrictions apply.
 */

/**
 * Get state of session and global config
 */
$app->get('/session', function () use ($app)
{
    if (!$app->session->isLoggedIn()) {
        return $app->status(401);
    }

    return $app->pass();
});

/**
 * Log In
 */
$app->post('/session', function () use ($app)
{
    $result = $app->session->login(
        ['email' => $app->get('email')],
        $app->get('password')
    );

    if (!$result) {
        $app->jsonError(400, 'invalid_grant', 'Invalid username or password');
    }

    return $app->pass();
});

/**
 * Log Out
 */
$app->delete('/session', function () use ($app)
{
    $app->session->logout();
    return $app->pass();
});

/**
 * Render standard response payload for session requests.
 * Invoked via `$app->pass()` from the method specific function.
 */
$app->match('/session', function () use ($app)
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

    return $app->json($json);
});
