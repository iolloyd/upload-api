<?php

use Cloud\Model\Video;

/**
 * Create new draft video
 */
$app->post('/videos', $app->authorize(), function() use ($app)
{
    $video = new Video();

    $video->setCreatedAt(new DateTime());
    $video->setCreatedBy($app->session->user());
    $video->setUpdatedAt(new DateTime());
    $video->setUpdatedBy($app->session->user());
    $video->setCompany($app->session->company());

    $app->em->persist($video);
    $app->em->flush();

    $app->json(201, $video);
});

/**
 * Get a video
 */
$app->get('/videos/:video', $app->authorize(), $app->find(), function(Video $video) use ($app)
{
    $app->json($video);
});

/**
 * Update a video
 */
$app->post('/videos/:video', $app->authorize(), $app->find(), function(Video $video) use ($app)
{
    if ($video->getStatus() != 'draft') {
        return $app->jsonError(400, 'invalid_status', 'Video must have status `draft` to update');
    }

    $app->em->transactional(function () use ($app, $video) {
        $video->setUpdatedAt(new DateTime());
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
    if ($video->getStatus() != 'draft') {
        return $app->jsonError(400, 'invalid_status', 'Video must have status `draft` to publish');
    }

    $app->em->transactional(function () use ($app, $video) {
        $video->setUpdatedAt(new DateTime());
        $video->setUpdatedBy($app->session->user());
        $video->setStatus(Video::STATUS_PENDING);
    });

    $app->json($video);
});
