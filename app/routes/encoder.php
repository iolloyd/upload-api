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

use CloudEncoder\PHPFFmpeg\ThumbnailCreator;
use CloudEncoder\PHPFFmpeg\VideoEncoder;
use CloudEncoder\PHPFFmpeg\VideoValidator;
use Symfony\Component\HttpFoundation\Request;

/*
 * /encoder/jobs
 */

// List all jobs
$app->get('/encoder/jobs', function () use ($app)
{

});

// Create a job
$app->post('/encoder/jobs', function (Request $request) use ($app)
{
    $video = $request->get('params');
    $validator = new VideoValidator();
    $result = $validator->process($video);
    return $app['single.response.json']($result, ['details']);

    // TODO push payload onto queue
    
    // TODO store temporary copy of video

});

/*
 * /encoder/jobs/{job}/encode
 */

// Encode a job
$app->get('/encoder/jobs/{job}/transcode', function ($job) use ($app)
{
    $encoder = new VideoEncoder();
    $result = $encoder->process($job);
    return $app['single.response.json']($result, ['details']);

})
->convert('job', 'converter.encoding_job:convert');


/*
 * /encoder/jobs/{job}
 */

// Retrieve and encoding job
$app->get('/encoder/jobs/{job}', function ($job) use ($app)
{
})
->convert('job', 'converter.encoding_job:convert');

