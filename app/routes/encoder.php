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

use CloudEncoder\S3VideoDownloader;
use CloudEncoder\S3VideoUploader;
use CloudEncoder\Transcoder;
use CloudEncoder\VideoValidator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Downloads, validates, transcodes and uploads an amazon s3 video
 */
$app->post('/encoder/jobs', function (Request $request) use ($app)
{
    $params = $request->request;
    $input  = $params->get('input');
    $output = $params->get('output');

    $s3     = $app['aws']['s3'];
    $bucket = $app['config']['aws']['bucket'];

    try {
        $downloader = new S3VideoDownloader();
        $downloader->process($s3, $bucket, $input);

        $validator = new VideoValidator();
        $metadata  = $validator->process($input);

        $transcoder = new Transcoder();
        $transcoder->process($input, $params->all());

        $uploader = new S3VideoUploader();
        $uploader->process($s3, $bucket, $output);

        return $app['single.response.json']($metadata, ['details']);

    } catch (\Exception $e) {
        // return a helpful error
    }
});

$app->get('/encoder/jobs', function (Request $request) use ($app)
{
    // TODO query resque for jobs and their status
});


