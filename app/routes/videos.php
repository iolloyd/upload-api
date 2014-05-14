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

    $app->em->persist($video);
    $app->em->flush();

    return $app->json(201, $video);
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
$app->post('/videos/{video}/publish', function(Video $video) use ($app)
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

/**
 * Create a new inbound upload and get parameters for the form to
 * AWS S3
 */
$app->post('/videos/:video/inbounds', function(Video $video) use ($app)
{
    $inbound = new VideoInbound($video);

    $app->em->persist($inbound);
    $app->em->flush();

    $form = new PostObject($app->s3, $app->config('s3.bucket'), [
        'ttd'                             => '+24 hours',
        'acl'                             => CannedAcl::PRIVATE_ACCESS,
        'success_action_status'           => 200,

        'key'                             => '^' . $inbound->getStorageChunkPath() . '/${filename}',

        'x-amz-meta-cx-video'             => $video->getId(),
        'x-amz-meta-cx-videoinbound'      => $inbound->getId(),
        'x-amz-meta-cx-company'           => $video->getCompany()->getId(),

        'x-amz-meta-flowchunknumber'      => '^',
        'x-amz-meta-flowchunksize'        => '^',
        'x-amz-meta-flowcurrentchunksize' => '^',
        'x-amz-meta-flowtotalsize'        => '^',
        'x-amz-meta-flowidentifier'       => '^',
        'x-amz-meta-flowfilename'         => '^',
        'x-amz-meta-flowrelativepath'     => '^',
        'x-amz-meta-flowtotalchunks'      => '^',
    ]);

    $form->prepareData();

    $json = [
        'id'         => $inbound->getId(),
        'video'      => ['id' => $video->getId()],
        'form'       => $form->getFormAttributes(),
        'fields'     => $form->getFormInputs(),
        'file_field' => 'file',
    ];

    $json['fields'] = array_filter($json['fields']);

    $app->json($json);
})
    ->convert('video', 'converter.video:convert');

/**
 * Complete chunk upload and combine chunks into single file
 */
$app->post('/videos/:video/inbounds/:videoinbound/complete',
    function(Video $video, VideoInbound $inbound) use ($app)
{
    if ($inbound->getVideo() != $video) {
        return $app->notFound();
    }

    if ($inbound->getStatus() != 'pending') {
        return $app->jsonError(400, 'invalid_status', 'Inbound must have status `pending` to finalize');
    }

    // init

    $app->em->transactional(function ($em) use ($inbound) {
        $inbound->setStatus('working');
    });

    $upload = new FlowUpload(
        $app->s3,
        $app->config('s3.bucket'),
        $inbound->getStorageChunkPath() . '/',
        []
    );

    // validate

    try {
        $upload->validate();
    } catch (RuntimeException $e) {
        $app->em->transactional(function ($em) use ($inbound) {
            $inbound->setStatus('error');
        });

        return $app->jsonError($e->getCode() ?: 400, 'invalid_upload', $e->getMessage());
    }

    // combine

    $app->em->transactional(function ($em) use ($video, $inbound, $upload) {
        $mimetypes = Mimetypes::getInstance();
        $meta      = $upload->getMetadata();

        $video->setFilename($meta['flowfilename']);
        $video->setFilesize($meta['flowtotalsize']);
        $video->setFiletype($mimetypes->fromFilename($meta['flowfilename']));

        /*
         * video.formats
         *   id, format = [ raw, 720p, mobile, foo, bar ], filename, storage_path,
         *   audio_codec, video_codec, ...
         *
         */

        $upload->copyToObject(sprintf('videos/%d/raw/%s',
            $video->getId(),
            $video->getFilename()
        ));

        $upload->deleteChunks();

        $inbound->setStatus('complete');
    });

    $app->status(204);
})
    ->convert('video', 'converter.video:convert')
    ->convert('inbound', 'converter.inbound:convert');

/**
 * Abort chunk upload and delete chunks
 */
$app->delete('/videos/:video/inbounds/:videoinbound', function(Video $video, VideoInbound $inbound) use ($app)
{
    if ($inbound->getVideo() != $video) {
        return $app->notFound();
    }

    if ($inbound->getStatus() != 'pending') {
        return $app->jsonError(400, 'invalid_status', 'Inbound must have status `pending` to delete');
    }

    $upload = new FlowUpload(
        $app->s3,
        $app->config('s3.bucket'),
        $key,
        []
    );

    $app->json(iterator_to_array($upload->deleteChunks()));
})
    ->convert('video', 'converter.video:convert')
    ->convert('inbound', 'converter.inbound:convert');
