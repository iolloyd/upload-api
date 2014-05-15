<?php
/**
 * @package  cloudxxx-api (http://www.cloud.xxx)
 *
 * @author    ReallyUseful <info@ruseful.com>
 * @copyright 2014 Really Useful Limited
 * @license   Proprietary code. Usage restrictions apply.
 */

use Cloud\Model\Video;
use Cloud\Model\VideoOutbound;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    return $app->json($video);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
->secure('ROLE_USER');

/**
 * Get list of videos
 */
$app->get('/videos', function(Request $request) use ($app)
{
    $first = $request->get('first') ?: 0;
    $limit = $request->get('limit') ?: 10;

    //$dql = "SELECT v, i from Cloud\Model\Video v JOIN v.inbounds i";
    $dql = "SELECT v FROM Cloud\Model\Video v";
    $qry = $app['em']->createQuery($dql)
        ->setFirstResult($first)
        ->setMaxResults($limit);

    $videos = new Paginator($qry, $fetchjoinCollection = true);
    $count = count($videos);

    return $app->json([
        'videos' => $videos, 
        'count'  => $count,
    ]);
});

/**
 * Create new draft video
 */
$app->post('/videos', function(Request $request) use ($app)
{
    $user = $app->session->user;
    $video = new Video($user);

    $video->setTitle($request->get('title'));
    $video->setDescription($request->get('description'));
    $video->setTags($request->get('tags'));
    $video->setStatus($request->get('status'));
    $video->setFilename($request->get('filename'));
    $video->setFilesize($request->get('filesize'));

    // TODO inbounds? outbounds?

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
        $video->setUpdatedBy($app->session->user());
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
        $video->setUpdatedBy($app->session->user());

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
