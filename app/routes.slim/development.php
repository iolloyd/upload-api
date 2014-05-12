<?php

use Cloud\Dev\Bootstrap;

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
    $gen = new \Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator();
    var_dump($gen->generateToken()); exit;

    $video = $app->em->getRepository('cx:Video')->findAll()[0];

    $inbound = new \Cloud\Model\VideoInbound($video);
    $app->em->persist($inbound);
    $app->em->flush();

    $inbounds = $app->em->getRepository('cx:VideoInbound')->findAll();
    var_dump($inbounds); exit;

    $app->json($videos);
});
