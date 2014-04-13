<?php

if (!$app->config('mode') == 'development') {
    return;
}

/**
 * Set up the dev database by nuking it and rebuilding it
 */
$app->get('/dev/setup', function () use ($app)
{
    R::nuke();

    $user = R::dispense('user');
    $user->import([
        'email'    => 'dev@cloud.xxx',
        'username' => 'dev',
        'password' => 'secure',
    ]);

    R::store($user);

    $app->json([
        'All systems are go. Yay!. The dev user credentials:',
        R::exportAll(R::findOne('user'))[0],
        'You only need to enter the email currently',
    ]);
});
