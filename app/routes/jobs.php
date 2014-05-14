<?php

function updatedJob($job)
{
    $status = new Resque_Job_Status($job->token);
    $job->status = $status->get();
    return $job;
}

function getUpdatedJobs($em) 
{
    $jobs = $em->getRepository('Cloud\Model\Job')->findAll();
    $jobs = array_map('updatedJob', $jobs);

    return $jobs;
}

$app->get('/admin/status', function() use ($app) 
{
    $jobs = getUpdatedJobs($app['em']);

    return $app->json($jobs);
});


