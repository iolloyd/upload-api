<?php

function getUpdatedJobs() {
    $jobs = R::findAll('job');
    foreach ($jobs as $job) {
        $status = new Resque_Job_Status($job->token);
        $job->status = $status->get();
        R::store($job);
    }

    return $jobs;
}

$app->get('/admin/status', function() use ($app) {
    $jobs = getUpdatedJobs();

    $latestJobs = array_reverse(array_slice($jobs, -10));
    $app->render('admin/status.html', ['jobs' => $latestJobs]);
});

$app->get('/admin/videos', function() use ($app) {
    $app->render('admin/videos/list.html', [
        'videos' => R::findAll('video')
    ]);
});

$app->get('/admin/videos/:id', function($id) use ($app) {
    $video = R::load('video', $id);
    $video = R::exportAll($video);
    $app->json($video);
});

