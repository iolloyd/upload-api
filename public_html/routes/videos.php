<?php
//require_once "../src/worker/FileUpload.php";

use Cloud\Worker\FileUpload as FileUpload;

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

function getAmazonEndpoint($config) {
    $aws = $config('amazon');
    $s3 = new S3($aws['access_key'], $aws['secret_key']);
    /*
    S3::putBucket($aws['bucket']);
    $inputFile = S3::inputFile($file, false);
    $contentType = getContentType($file);
    S3::putObject($inputFile, $bucketName, $uploadName, S3::AUTHENTICATED_READ);
     */
}

function getContentType($file) {
    return 'video/mpeg';
}

function getEndpoint($bucket) {
    return 'http://'.$bucket.'.amazonaws.com';
}

function getPolicy($bucket, $redirect, $contentType, $acl='public-read') {
    $expires = "2015-12-01T12:00:00.000Z";
    $policy = <<<POLICY
        { "expiration": $expires,
            "conditions": [
                {"bucket": $bucket},
                ["starts-with", "$key", "user/eric/"],
                {"acl": $acl},
                {"success_action_redirect": $redirect},
                ["eq", "$Content-Type", $contentType]
            ]
        }
POLICY;
}

function queueFileUpload($redisBackend, $video) {
    $args = [
        'source' => $video->source,
        'destination' => $video->destination
        ];
    Resque::setBackend($redisBackend);
    $token = Resque::enqueue('video_upload', 'Cloud\Worker\FileUpload', $args, true); 
    $job = R::dispense('job');
    $job->token = $token;
    $status = new Resque_Job_Status($token);
    $job->status = $status->get();
    $job->created = date('Y-m-d h:i:s');
    R::store($job); 
    return $token;
}

// Dev helper form
$app->get('/videos/new', function() use ($app) {
    $app->render('new.html'); 
});

$app->get('/video-upload-form', function() use ($app, $config) {
    $aws = $config('amazon');
    $bucket = $aws['bucket'];

    $app->render('video-upload-form.html', [
        'key' => '/users/iolloyd',
        'acl' => 'public-read',
        'success_redirect' => $_SERVER['HTTP_HOST'] . '/amazon_ok',
        'amazonEndpoint' => getEndpoint($bucket),
        'aws_access_key_id' => $aws['access_key'],
    ]);
});

$app->get('/videos', function() use ($app) {
    $videos = getVideos();
    $app->json(R::exportAll($videos));
});

$app->get('/videos/:id', function($id) use ($app) {
    $data = array('id' => $id);
    $app->json(getVideo($id));
});

$app->get('/admin/status', function() use ($app) {
    $jobs = R::findAll('job');
    $app->render('admin/status.html', ['jobs' => $jobs]);
});

$app->get('/videos/:id/upload/process', function($id) use ($app, $config) {
    $video = R::load('video', $id);
    $backend = $config('redis')['backend'];
    $token = queueFileUpload($backend, $video);
    echo $token;
});
