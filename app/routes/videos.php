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

/**
 * Get list of videos
 */
$app->get('/videos', function(Request $request) use ($app)
{
    $groups = ['list', 'list.videos'];
    $options = ['filterFields' => ['status', 'site']];
    return $app['paginator.response.json']('cx:video', $groups, $options);
});

/**
 * Create new draft video
 */
$app->post('/videos', function(Request $request) use ($app)
{
    $video = new Video($app['user']);

    $app['em']->persist($video);
    $app['em']->flush();

    return $app['single.response.json']($video, ['details', 'details.videos']);
});

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    $groups = ['details', 'details.videos'];
    return $app['single.response.json']($video, $groups);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;

/**
 * Update a video
 */
$app->post('/videos/{video}', function(Video $video, Request $request) use ($app)
{
    if (!$video->isDraft()) {
        $app['logger.api']->error(
            "Tried updating a non-draft status video with id {video}",
            ['video' => $video->getId()]
        );

        return $app->json([
            'error' => 'invalid_status',
            'error_details' => 'Video must be in draft status',
        ], 400);
    }

    $app['em']->transactional(function () use ($app, $video, $request) {
        $video->setTitle($request->get('title'));
        $video->setDescription($request->get('description'));

        if ($primaryCategoryId = $request->get('primary_category')['id']) {
            $video->setPrimaryCategory($app['converter.category']->convert($primaryCategoryId));
        } else {
            $video->setPrimaryCategory(null);
        }

        $video->setSecondaryCategories(
            array_map(function ($d) use ($app) {
                return $app['converter.category']->convert($d['id']);
            }, (array) $request->get('secondary_categories'))
        );

        $video->setTags(
            array_map(function ($d) use ($app) {
                return $app['converter.tag']->convert($d['id']);
            }, (array) $request->get('tags'))
        );
    });

    return $app['single.response.json']($video, ['details', 'details.videos']);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;

/**
 * Publish a draft video when it's ready
 */
$app->post('/videos/{video}/publish', function(Video $video) use ($app)
{
    if (!$video->isDraft()) {
        $app['logger.api']->error(
            "Tried publishing a non-draft status video with id {video}",
            ['video' => $video->getId()]
        );

        return $app->json([
            'error' => 'invalid_status',
            'error_details' => 'Video must be in draft status',
        ], 400);
    }

    $app['em']->transactional(function ($em) use ($app, $video) {
        $video->setStatus(Video::STATUS_PENDING);

        $tubeusers = $app['em']->getRepository('cx:tubesiteUser')->findAll();

        foreach ($tubeusers as $tubeuser) {
            $outbound = new VideoOutbound($video);

            $outbound->setTubesite($tubeuser->getTubesite());
            $outbound->setTubesiteUser($tubeuser);

            $em->persist($outbound);
        }
    });

    //TODO add redis enqueueing
    //Resque::enqueue(
        //'default',
        //'CloudOutbound\YouPorn\Job\DemoCombined',
        //['videooutbound' => $outbound->getId()]
    //);

    return $app['single.response.json'](
        $video,
        ['details', 'details.videos', 'details.outbounds']
    );
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;
