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

use Aws\S3\Enum\CannedAcl;
use Aws\S3\Model\PostObject;
use Cloud\Model\Video;
use Cloud\Model\VideoInbound;
use Cloud\Aws\S3\Model\FlowUpload;
use GuzzleHttp\Mimetypes;

/**
 * Create a new inbound upload and get
 * parameters for the form to * AWS S3
 */
$app->post('/videos/{video}/inbounds', function(Video $video) use ($app)
{
    $inbound = new VideoInbound($video, $app['security']->getToken()->getUser());

    // TODO set $inbound->filename/size/type/expiresAt

    $app['em']->persist($inbound);
    $app['em']->flush();

    $form = new PostObject($app['aws']->get('s3'), $app['config']['aws']['bucket'], [
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

    return $app->json($json, 201);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;

/**
 * Complete chunk upload and combine chunks into single file
 */
$app->post('/videos/{video}/inbounds/{inbound}/complete', function(Video $video, VideoInbound $inbound) use ($app)
{
    if ($inbound->getVideo() != $video) {
        return $app->notFound();
    }

    if ($inbound->getStatus() != 'pending') {
        return $app->jsonError(400, 'invalid_status', 'Inbound must have status `pending` to finalize');
    }

    // init

    $app['em']->transactional(function ($em) use ($inbound) {
        $inbound->setStatus('working');
    });

    $upload = new FlowUpload(
        $app['aws']->get('s3'), 
        $app['config']['aws']['bucket'],
        $inbound->getStorageChunkPath() . '/',
        []
    );

    // validate
    try {
        $upload->validate();
    } catch (RuntimeException $e) {
        $app['em']->transactional(function ($em) use ($inbound) {
            $inbound->setStatus('error');
        });

        return $app->json([
            'error' => 'invalid_upload',
            'error_details' => $e->getMessage(),
        ], 400);
    }

    // combine
    $app['em']->transactional(function ($em) use ($video, $inbound, $upload) {
        $mimetypes = Mimetypes::getInstance();
        $meta      = $upload->getMetadata();

        $video->setFilename($meta['flowfilename']);
        $video->setFilesize($meta['flowtotalsize']);
        $video->setFiletype($mimetypes->fromFilename($meta['flowfilename']));

        /**
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

    $groups = ['details', 'details.videos', 'details.inbounds'];
    return $app['single.response.json']($video, $groups);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
->assert('inbound', '\d+')
->convert('inbound', 'converter.inbound:convert')
;

/**
 * Abort chunk upload and delete chunks
 */
$app->delete('/videos/{video}/inbounds/{inbound}', function(Video $video, VideoInbound $inbound) use ($app)
{
    if ($inbound->getVideo() != $video) {
        return $app->notFound();
    }

    if ($inbound->getStatus() != 'pending') {
        return $app->jsonError(400, 'invalid_status', 'Inbound must have status `pending` to delete');
    }

    $upload = new FlowUpload(
        $app['aws']->get('s3'), 
        $app['config']['aws']['bucket'],
        $key,
        []
    );

    $app->json(iterator_to_array($upload->deleteChunks()));
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
->assert('inbound', '\d+')
->convert('inbound', 'converter.inbound:convert')
;
