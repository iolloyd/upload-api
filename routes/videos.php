<?php
use Cloud\Worker\FileUpload;

function createJob($token) {
    $job = R::dispense('job');
    $job->token = $token;
    $job->status = (new Resque_Job_Status($token))->get();
    $job->created = date('Y-m-d h:i:s');
    R::store($job); 

    return $job;
}

function queueFileUpload($redisBackend, $user, $video, $filename) {
    Resque::setBackend($redisBackend);
    $args = [
        'source' => $video->source,
        'destination' => $video->destination,
        'filename' => $filename, 
    ];
    $token = Resque::enqueue('video_upload', 'Cloud\Worker\FileUpload', $args, true); 
    createJob($token);

    return $token;
}

function getUpdatedJobs() {
    $jobs = R::findAll('job');
    foreach ($jobs as $job) {
        $status = new Resque_Job_Status($job->token);
        $job->status = $status->get();
        R::store($job);
    }

    return $jobs;
}

$app->get('/videos/', function() use ($app) {
    $videos = R::findAll('video');
    $app->json(R::exportAll($videos));
});

$app->get('/videos/:id', function($id) use ($app) {
    $video = R::load('video', $id);
    $app->json(R::exportAll($video));
})->conditions(['id' => '\d']);

$app->get('/videos/:id/process(/:name)', function($id, $name='test.mp4') use ($app, $config) {
    $video = R::load('video', $id);
    $backend = $config('redis')['backend'];
    $token = queueFileUpload($backend, $app->user, $video, $name);
    $app->json(['token' => $token]);
});

$app->get('/videos/:id/edit', function($id) use ($app) {
    $video = R::load('video', $id);
    $app->render('forms/video_edit.html', [
        'video' => R::load('video', $id)
    ]);
});

$app->post('/videos/edit', function() use ($app) {
    $video = R::load('video', $_POST['id']);
    $video->import($_POST);
    R::store($video);
    $app->redirect($app->request->getReferrer());
});

$app->post('/videos/', function() use ($app) {
    $tags = (!empty($_POST['tag']))
        ? array_keys($_POST['tag'])
        : [];

    $video = R::dispense('video');
    $video->import($_POST, 'title,desc');
    R::tag($video, $tags); 
    R::store($video);

    $app->json(R::exportAll($video));
});
