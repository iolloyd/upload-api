<?php

$app->get('/admin/status', function() use ($app) {
    $jobs = getUpdatedJobs();

    $latestJobs = array_slice($jobs, -10);
    $app->render('admin/status.html', ['jobs' => $latestJobs]);
});

