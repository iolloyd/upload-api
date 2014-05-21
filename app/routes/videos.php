<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

use Cloud\Model\Video;
use Cloud\Model\VideoOutbound;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerBuilder;

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    return $app->json($video);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;

/**
 * Get list of videos
 */
$app->get('/videos', function(Request $request) use ($app)
{
    $groups = ['details', 'details.companies'];
    $pagedView = $app['paginator.response.json']('cx:video', $groups);
    return $pagedView;
});

/**
 * Create new draft video
 */
$app->post('/videos', function(Request $request) use ($app)
{
    $video = new Video($app['user']);

    $app['em']->persist($video);
    $app['em']->flush();

    return $app->json($video, 201);
});


/**
 * Update a video
 */
$app->post('/videos/{video}', function(Video $video) use ($app)
{
    if (!$video->isDraft()) {
        return $app->jsonError(
            400,
            'invalid_status',
            'Video must be in draft status'
        );
    }

    $app['em']->transactional(function () use ($app, $video) {
        $video->setUpdatedBy($app['user']);
        $video->setTitle($app->param('title'));
        $video->setDescription($app->param('description'));
    });

    $app->json($video);
});

/**
 * Publish a draft video when it's ready
 */
$app->post('/videos/{video}/publish', function(Video $video) use ($app)
{
    if (!$video->isDraft()) {
        return $app->jsonError(
            400, 'invalid_status', 'Video must be in draft status'
        );
    }

    $outbound = new VideoOutbound($video);

    $app['em']->transactional(function ($em) use ($app, $video, $outbound) {
        $video->setStatus(Video::STATUS_PENDING);
        $video->setUpdatedBy($app['user']);

        // TODO: refactor

        $inbound  = $video->getVideoInbounds()->last();
        $tubeuser = $app['em']->getRepository('cx:tubesiteuser')->findAll()[0];

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
