<?php
use Cloud\Worker\FileUpload;

function createJob($token, $filename) {
    $job = R::dispense('job');
    $job->filename = $filename;
    $job->token = $token;
    $job->status = (new Resque_Job_Status($token))->get();
    $job->created = date('Y-m-d h:i:s');
    R::store($job); 

    return $job;
}

function queueFileUpload($redisBackend, $user, $video, $filename) {
    Resque::setBackend($redisBackend);
    $args = [
        'source' => $video->path,
        'destination' => $video->destination,
        'filename' => $filename, 
    ];
    $token = Resque::enqueue('video_upload', 'Cloud\Worker\FileUpload', $args, true); 
    createJob($token, $filename);

    return $token;
}

$app->get('/videos/:id', function($id) use ($app) {
    $video = R::load('video', $id);
    $app->json(R::exportAll($video));
})->conditions(['id' => '\d']);

$app->get('/videos/', function() use ($app) {
    $videos = R::findAll('video');
    $app->json(R::exportAll($videos));
});

$app->get('/videos/:id/process(/:name)', function($id, $name='test.mp4') use ($app, $config) {
    $video = R::load('video', $id);
    $backend = $config('redis')['backend'];
    $token = queueFileUpload($backend, $app->user, $video, $name);
    $app->redirect('/admin/status');
});

$app->get('/videos/:id/edit', function($id) use ($app) {
    $video = R::load('video', $id);
    $app->render('forms/video_edit.html', [
        'tags' => R::findAll('tag'),
        'video' => R::load('video', $id),
        'videoTags' => R::tag($video)
    ]);
});

$app->post('/videos/edit', function() use ($app) {
    $video = R::load('video', $_POST['id']);
    $video->import($_POST, 'title,description,path,destination');
    R::store($video);
    $app->redirect($app->request->getReferrer());
});

$app->post('/videos/', function() use ($app) {
    $tags = (!empty($_POST['tag']))
        ? array_keys($_POST['tag'])
        : [];

    $video = R::dispense('video');
    $video->import($_POST, 'title,description');
    R::tag($video, $tags); 
    R::store($video);

    $app->json(R::exportAll($video));
});
