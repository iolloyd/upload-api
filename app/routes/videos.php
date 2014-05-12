<?php

use Cloud\Model\Video;
use Cloud\Model\VideoOutbound;

/**
 * Get a video
 */
$app->get('/videos/{video}', function(Video $video) use ($app)
{
    $app->json($video);
})
->convert('video', 'converter.video:convert')
->secure('ROLE_USER');
