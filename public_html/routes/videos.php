<?php
require_once "../src/FileUploader.php";

use Cloud\FileUploader as FileUploader;

function getVideos() {
    $videos = R::findAll('video');
    return $videos;
}

function getVideo($id) {
    $video = R::find('video', $id);
    return $video;
}

function saveVideoData($app, $title, $description, $path) {
    $user = $app->user;
    $video = R::dispense('video');
    $video->title = $title;
    $video->description = $description;
    $video->path = $path;
    $user->ownVideoList[] = $video;
    return R::store($video);
}

function saveVideoFile($uploadDir, $info) {
    $from = $info['tmp_name'];
    $to = "$uploadDir/{$info['name']}";
    $ok = move_uploaded_file($from, $to);
    if (!$ok) {
        return false;
    }

    return $to;
}

// Dev helper form
$app->get('/videos/new', function() use ($app) {
    $app->render('new.html'); 
});

$app->get('/videos', function() use ($app) {
    $videos = getVideos();
    $app->json(R::exportAll($videos));
});

$app->get('/videos/:id', function($id) use ($app) {
    $data = array('id' => $id);
    $app->json(getVideo($id));
});

$app->post('/videos/upload', function() use ($app, $config) {
    $uploadDir = $config('upload')['dir'];
    $path = saveVideoFile($uploadDir, $_FILES['video']);
    if ($path) {
        $id = saveVideoData($app, $_POST['title'], $_POST['description'], $path);

        echo 'stored video info with id ' . $id . ' in ' . $path . '</br>';

    } else {
        echo 'Oops, upload no workie';
    }
});

$app->get('/videos/send/:id', function($id) use ($app, $config) {
    $video = R::load('video', $id);
    $redisBackend = $config('redis')['backend'];
    Resque::setBackend($redisBackend);
    $args = ['paysite' => $video->path];
    $token = Resque::enqueue('video_upload', 'FileUploader', $args, true); 
    echo $token;
});

$app->put('/videos/:id', function($id) use ($app) {
});

