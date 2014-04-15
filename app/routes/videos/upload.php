<?php

use Aws\S3\Enum\CannedAcl;
use Aws\S3\Model\PostObject;

/**
 * Get parameters for a video upload form to AWS S3
 */
$app->get('/videos/:video/upload', $app->authorize(), function($video) use ($app)
{
    $id = uniqid();
    $key = '^uploads/' . $video . '/' . $id . '/${filename}';

    $form = new PostObject($app->s3, $app->config('s3.bucket'), [
        'acl' => CannedAcl::PRIVATE_ACCESS,
        'key' => $key,
        'ttd' => '+5 hours',
        'x-aws-meta-video' => $video,
    ]);

    $form->prepareData();

    $json = [
        'upload_id'  => $id,
        'form'       => $form->getFormAttributes(),
        'fields'     => $form->getFormInputs(),
        'file_field' => 'file',
    ];

    $app->json($json);
});


/**
 * Verify a completed upload and finalize it
 */
$app->post('/videos/:video/upload', $app->authorize(), function($video) use ($app)
{
});

