<?php

/**
 * Get parameters for a video upload form to AWS S3
 */
$app->get('/videos/:video/upload', $app->authorize(), function ($video) use ($app)
{
    $uploadKey = uniqid();

    // build the upload policy

    $expiration = new DateTime();
    $expiration->add(DateInterval::createFromDateString('5 hours'));
    $expiration->setTimezone(new DateTimeZone('UTC'));

    $policy = [
        'expiration' => $expiration->format('Y-m-d\TH:i:s\Z'),
        'conditions' => [
            ['bucket' => $app->config('s3.bucket')],
            ['content-length-range', 0, 1024*1024*1024*10], // 10GB
            ['starts-with', '$key', 'uploads/1/'],
            ['x-aws-meta-video' => '1'],
        ],
    ];

    $policyString = base64_encode(json_encode($policy, JSON_UNESCAPED_SLASHES));
    $signatureString = base64_encode(hash_hmac('sha1', $policyString, $app->config('s3.secret'), true));

    // build the form fields

    $json = [
        'form' => [
            'action'  => 'https://' . $app->config('s3.bucket') . '.s3.amazonaws.com/',
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ],
        'fields'=> [
            'key'              => 'uploads/1/' . $uploadKey . '/${filename}',
            'x-aws-meta-video' => '1',
            'AWSAccessKeyId'   => $app->config('s3.key'),
            'policy'           => $policyString,
            'signature'        => $signatureString
        ],
        'file_field'=> 'file'
    ];

    $app->json($json);
});
