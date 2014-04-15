<?php

function getAwsPayload($app, $video)
{
    $bucket = $app->config('s3.bucket');
    $policy = getPolicy($bucket);
    $json = [
        'form' => [
            'action'  => 'https://' . $bucket . '.s3.amazonaws.com/',
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ],
        'fields'=> [
            'key'              => getUploadKey($video),
            'x-aws-meta-video' => '1',
            'AWSAccessKeyId'   => $app->config('s3.key'),
            'policy'           => $policy, 
            'signature'        => getSignature($policy, $app->config('s3.secret'))
        ],
        'file_field'=> 'file'
    ];

    return $json;
}

function getPolicy($bucket)
{
    $policy = [
        'expiration' => getFormattedExpiration(),
        'conditions' => [
            ['bucket' => $bucket],
            ['content-length-range', 0, 1024*1024*1024*10], // 10GB
            ['starts-with', '$key', 'uploads/1/'],
            ['x-aws-meta-video' => '1'],
        ],
    ];

    return $policy;
}

function getFormattedExpiration($hours=5)
{
    $time = $hours . ' hours';
    $expiration = new DateTime();
    $expiration->add(DateInterval::createFromDateString($time));
    $expiration->setTimezone(new DateTimeZone('UTC'));

    return $expiration->format('Y-m-d\TH:i:s\Z');
}

function getUploadKey($filename)
{
    $key = 'uploads/1/' . uniqid() . '/'. $filename;
    return $key;

}


function getSignature($policy, $secret)
{
    $policyString = base64_encode(json_encode($policy, JSON_UNESCAPED_SLASHES));
    $signature = base64_encode(hash_hmac('sha1', $policyString, $secret));
    return $signature;
}


