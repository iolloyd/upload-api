<?php

use Cloud\Model\Video;
use Cloud\Model\VideoOutbound;

/**
 * Create new draft video
 */
$app->post('/videos', $app->authorize(), function() use ($app)
{
    $video = new Video();

    $video->setCreatedBy($app->session->user());
    $video->setUpdatedBy($app->session->user());
    $video->setCompany($app->session->company());

    $app->em->persist($video);
    $app->em->flush();

    $app->json(201, $video);
});

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    $app->json($video);
})->convert('video', 'converter.video:convert');

/**
 * Get list of videos
 */
$app->get('/videos', function() use ($app)
{
    $videos = $app->em->getRepository("\Cloud\Model\Video")->findAll();
    $app->json($videos);
});

/**
 * Update a video
 */
$app->post('/videos/:video', $app->authorize(), $app->find(), function(Video $video) use ($app)
{
    if (!$video->isDraft()) {
        return $app->jsonError(400, 'invalid_status', 'Video must be in draft status');
    }

    $app->em->transactional(function () use ($app, $video) {
        $video->setUpdatedBy($app->session->user());
        $video->setTitle($app->param('title'));
        $video->setDescription($app->param('description'));
    });

    $app->json($video);
});

/**
 * Publish a draft video when it's ready
 */
$app->post('/videos/:video/publish', $app->authorize(), $app->find(), function(Video $video) use ($app)
{
    if (!$video->isDraft()) {
        return $app->jsonError(
            400, 'invalid_status', 'Video must be in draft status'
        );
    }

    $outbound = new VideoOutbound($video);

    $app->em->transactional(function ($em) use ($app, $video, $outbound) {
        $video->setStatus(Video::STATUS_PENDING);
        $video->setUpdatedBy($app->session->user());

        // TODO: refactor

        $inbound  = $video->getVideoInbounds()->last();
        $tubeuser = $app->em->getRepository('cx:tubesiteuser')->findAll()[0];

        $outbound->setTubesite($tubeuser->getTubesite());
        $outbound->setTubesiteUser($tubeuser);
        $outbound->setFilename($video->getFilename());
        $outbound->setFilesize($video->getFilesize());
        $outbound->setFiletype($video->getFiletype());

        $em->persist($outbound);
    });

    Resque::enqueue(
        'default',
        'CloudOutbound\YouPorn\Job\DemoCombined',
        ['videooutbound' => $outbound->getId()]
    );

    $app->json($video);
});
