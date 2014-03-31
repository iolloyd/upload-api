<?php

function getVideos() {
    $videos = R::findAll('video');
    return $videos;
}


$app->get('/videos', function() use ($app) {
    $videos = getVideos();
    $app->json(R::exportAll($videos));
    //$app->render('list.html', array('videos' => $videos));
});

$app->get('/videos/new', function() use ($app) {
    $app->render('new.html'); 
});

$app->get('/videos/:id', function($id) use ($app) {
    $data = array('id' => $id);
    $app->render('video.html', $data); 
});

$app->post('/videos/upload', function() use ($app, $config) {
    $dbInfo = $config('db');
    $info = $_POST;
    $user = $app->user;
    $video = R::dispense('video');
    $video->import($_POST, 'title, description');
    $id = R::store($video);
    echo 'stored video info with id ' . $id . '</br>';
});

$app->put('/videos/:id', function($id) use ($app) {
});

