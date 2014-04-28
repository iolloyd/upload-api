<?php

use Cloud\Dev\Bootstrap;
use Cloud\Model\Video;
use Cloud\Model\User;

if (!$app->config('mode') == 'development') {
    return;
}

/**
 * Set up the dev database by nuking it and rebuilding it
 */
$app->get('/dev/setup', function() use ($app)
{

    Bootstrap::createDevData($app->entityManager);
    $em = $app->entityManager;
    $users = $em->getRepository("Cloud\Model\User")->findAll();
    $videos = $em->getRepository("Cloud\Model\Video")->findAll();
    $app->json([
        'All systems are go. Yay!. The dev user credentials:',
        'users' => $users,
        'videos' => $videos,
        'You only need to enter the email',
    ]);
});

$app->get('/dev/test', function() use ($app) {
    $video = Video::findAll();
    $users = User::findAll();
});
