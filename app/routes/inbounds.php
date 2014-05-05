<?php

use Aws\S3\Enum\CannedAcl;
use Aws\S3\Model\PostObject;
use Cloud\Model\Video;
use Cloud\Model\VideoInbound;
use Cloud\Aws\S3\Model\FlowUpload;

/**
 * Create a new inbound upload and get parameters for the form to
 * AWS S3
 */
$app->post('/videos/:video/inbounds', $app->authorize(), $app->find(), function(Video $video) use ($app)
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
});

/**
 * Complete chunk upload and combine chunks into single file
 */
$app->post('/videos/:video/inbounds/:videoinbound/complete', $app->authorize(), $app->find(),
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
        $meta = $upload->getMetadata();

        $video->setFilename($meta['flowfilename']);

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
});

/**
 * Abort chunk upload and delete chunks
 */
$app->delete('/videos/:video/inbounds/:videoinbound', $app->authorize(), $app->find(),
    function(Video $video, VideoInbound $inbound) use ($app)
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
});
