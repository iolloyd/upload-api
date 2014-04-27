<?php

use Aws\S3\Enum\CannedAcl;
use Aws\S3\Model\PostObject;
use Cloud\Aws\S3\Model\FlowUpload;

/**
 * Get parameters for a video upload form to AWS S3
 */
$app->get('/videos/:video/upload', $app->authorize(), function($video) use ($app)
{
    $id  = uniqid();
    $key = '^uploads/' . $video . '/' . $id . '/${filename}';

    $form = new PostObject($app->s3, $app->config('s3.bucket'), [
        'acl' => CannedAcl::PRIVATE_ACCESS,
        'key' => $key,
        'ttd' => '+24 hours',
        'success_action_status' => 200,

        'x-amz-meta-video' => $video,

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
        'upload_id'  => $id,
        'form'       => $form->getFormAttributes(),
        'fields'     => $form->getFormInputs(),
        'file_field' => 'file',
    ];
    $json['fields'] = array_filter($json['fields']);

    $app->json($json);
});


/**
 * Verify a completed upload and finalize it
 */
$app->post('/videos/:video/upload', $app->authorize(), function($video) use ($app)
{
});

/**
 * Complete chunk upload and combine chunks into single file
 */
$app->get('/videos/:video/upload/:upload', $app->authorize(), function($video, $upload) use ($app)
{
    $key = 'uploads/' . $video . '/' . $upload . '/';

    $upload = new FlowUpload(
        $app->s3,
        $app->config('s3.bucket'),
        $key,
        []
    );

    // validate

    try {
        $upload->validate();
    } catch (RuntimeException $e) {
        return $app->jsonError($e->getCode() ?: 400, 'invalid_upload', $e->getMessage());
    }

    // combine

    $meta = $upload->getMetadata();
    $target = 'videos/' . $video . '/raw/' . $meta['flowfilename'];

    $result = $upload->copyToObject($target);

    $app->json([
        'result' => iterator_to_array($result),
        'metadata' => $upload->getMetadata(),
    ]);
});

/**
 * Abort chunk upload and delete chunks
 */
$app->delete('/videos/:video/upload/:upload', $app->authorize(), function($video, $upload) use ($app)
{
    $key = 'uploads/' . $video . '/' . $upload . '/';

    $upload = new FlowUpload(
        $app->s3,
        $app->config('s3.bucket'),
        $key,
        []
    );

    $app->json(iterator_to_array($upload->deleteChunks()));
});
