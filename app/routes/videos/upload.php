<?php

/**
 * Get parameters for a video upload form to AWS S3
 */
$app->get('/videos/:video/upload', $app->authorize(), function($video) use ($app)
{
    $json = getAwsPayload($app, $video);
    $app->json($json);
});

