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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    $video = $app['em']->find('cx:video', $id);
    return $app->json($video);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
// ->secure('ROLE_USER');
;

/**
 * Get list of videos
 */
$app->get('/videos', function(Request $request) use ($app)
{
    $videos = $app['em']
        ->getRepository('cx:video')
        ->matching(new Criteria());

    $adapter = new DoctrineCollectionAdapter($videos);
    $pager = new Pagerfanta($adapter); 

    $videos = $pager->getCurrentPageResults();

    echo 'total:' . $pager->count() . '<br/>';
    echo 'returned:' . count($videos) . '<br/>'; 
    echo $videos[0]->getTitle();
die;
    $first = $request->get('first') ?: 0;
    $limit = $request->get('limit') ?: 10;


    return $app->json([
        'videos' => $videos, 
        'count'  => $count,
    ]);
})
->secure('ROLE_USER');

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
