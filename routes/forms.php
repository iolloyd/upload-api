<?php

$app->get('/video-upload-form', function() use ($app) {
    $fields = [
        'title' => 'title',
        'description' => 'desc' 
    ];

    $videos = R::findAll('video');
    $tags = R::findAll('tag');

    $app->render('/forms/video-upload.html', [
        'videos' => $videos,
        'action' => '/videos/', 
        'fields' => $fields,
        'tags' => $tags
    ]);
});

