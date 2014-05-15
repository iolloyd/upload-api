<?php

/**
 * Get state of session and global config
 */
$app->get('/session', function () use ($app)
{
    if (!$app['security']->getToken()) {
        $app->status(401);
    }

    $json = [
        'endpoints' => [
            //'default' => $app['config']['baseurl'],
        ],
        'config' => [
            'env' => $app['env'],
        ],
    ];

    $token = $app['security']->getToken();

    if ($app['security']->isGranted('ROLE_USER')) {
        $json['user'] = $token;
        //$json['company'] = $user->getCompany();
    }

    return $app->json($json);
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
//$app->any('/session', function () use ($app)
//{
    //$json = [
        //'endpoints' => [
            //'default' => $app->config('baseurl'),
        //],
        //'config' => [
            //'mode' => $app->config('mode'),
        //],
    //];

    //$user = $app['security']->getToken();

    //if ($user) {
        //$json['user'] = $user;
        //$json['company'] = $user->getCompany();
    //}

    //$app->json($json);
//});
