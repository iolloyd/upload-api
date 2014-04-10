<?php
use Cloud\Worker\FileUpload;

function createJob($token) {
    $job = R::dispense('job');
    $job->token   = $token;
    $job->status  = (new Resque_Job_Status($token))->get();
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

$app->get('/videos/new', function() use ($app) {
    $app->render('new.html'); 
});

$app->get('/videos', function() use ($app) {
    $videos = R::findAll('video');
    $app->json(R::exportAll($videos));
});

$app->get('/videos/:id', function($id) use ($app) {
    $video = R::load('video', $id);
    $app->json(R::exportAll($video));
});

$app->get('/videos/:id/upload/process', function($id) use ($app, $config) {
    $video = R::load('video', $id);
    $backend = $config('redis')['backend'];
    $token = queueFileUpload($backend, $app->user, $video, 'ilove.mp4');
    echo $token;
});

$app->get('/admin/status', function() use ($app) {
    $jobs = getUpdatedJobs();

    $latestJobs = array_slice($jobs, -10);
    $app->render('admin/status.html', ['jobs' => $latestJobs]);
});
