<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

use Aws\S3\Enum\CannedAcl;
use Aws\S3\Model\PostObject;
use Cloud\Aws\S3\Model\FlowUpload;
use Cloud\Model\Video;
use Cloud\Model\VideoInbound;
use Cloud\Model\VideoFile\InboundVideoFile;

/**
 * Create a new inbound upload and get
 * parameters for the form to * AWS S3
 */
$app->post('/videos/{video}/inbounds', function(Video $video) use ($app)
    {
        $inbound = new VideoInbound($video);

        // TODO set $inbound->expiresAt

        $app['em']->persist($inbound);
        $app['em']->flush();

        $form = new PostObject($app['aws']->get('s3'), $app['config']['aws']['bucket'], [
            'ttd'                             => '+24 hours',
            'acl'                             => CannedAcl::PRIVATE_ACCESS,
            'success_action_status'           => 200,

            'key'                             => '^' . $inbound->getTempStoragePath() . '/${filename}',

            'x-amz-meta-cx-video'             => $video->getId(),
            'x-amz-meta-cx-videoinbound'      => $inbound->getId(),
            'x-amz-meta-cx-company'           => $video->getCompany()->getId(),

            'x-amz-meta-flowchunknumber'      => '^',
            'x-amz-meta-flowchunksize'        => '^',
            'x-amz-meta-flowcurrentchunksize' => '^',
            'x-amz-meta-flowtotalsize'        => '^',
            'x-amz-meta-flowidentifier'       => '^',
            'x-amz-meta-flowfilename'         => '^',
            'x-amz-meta-flowrelativepath'     => '^',
            'x-amz-meta-flowtotalchunks'      => '^',
        ]);

        $form->prepareData();

        $json = [
            'id'         => $inbound->getId(),
            'video'      => ['id' => $video->getId()],
            'form'       => $form->getFormAttributes(),
            'fields'     => $form->getFormInputs(),
            'file_field' => 'file',
        ];

        $json['fields'] = array_filter($json['fields']);

        return $app->json($json, 201);
    }
)
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
;

/**
 * Complete chunk upload and combine chunks into single file
 */
$app->post('/videos/{video}/inbounds/{inbound}/complete', function(Video $video, VideoInbound $inbound) use ($app)
{
    if ($inbound->getVideo() != $video) {
        $app->abort(404);
    }

    if ($inbound->getStatus() != 'pending') {
        return $app->json([
            'error' => 'invalid_status',
            'error_description' => 'Inbound must have status `pending` to finalize',
        ], 400);
    }

    // init

    $app['em']->transactional(function ($em) use ($inbound) {
        $inbound->setStatus('working');
    });

    $upload = new FlowUpload(
        $app['aws']->get('s3'),
        $app['config']['aws']['bucket'],
        $inbound->getTempStoragePath() . '/',
        []
    );

    // validate chunks and get metadata

    try {
        $upload->validate();
    } catch (RuntimeException $e) {
        $app['em']->transactional(function ($em) use ($inbound) {
            $inbound->setStatus('error');
        });

        return $app->json([
            'error' => 'invalid_upload',
            'error_details' => $e->getMessage(),
        ], 400);
    }

    // insert videofile model

    $videoFile = new InboundVideoFile($inbound);

    $videoFile->setFilename($upload->getFilename());
    $videoFile->setFilesize($upload->getFilesize());
    $videoFile->setFiletype($upload->getFiletype());

    $app['em']->transactional(function ($em) use ($videoFile) {
        $em->persist($videoFile);
    });

    // recombine chunks

    $upload->copyToObject($videoFile->getStoragePath());
    $upload->deleteChunks();

    // TODO: use Flysystem abstractions
    //
    //  - inbounds://23/85/filename.mp4
    //  - videofiles://23/85/filename.mp4

    $app['em']->transactional(function ($em) use ($inbound, $videoFile) {
        $inbound->setStatus('complete');
        $videoFile->setStatus('pending');
    });

    // validate videofile and get metadata

    $s3 = $app['aws']->get('s3');
    $zencoder = $app['zencoder'];

    $inputUrl = $s3->getObjectUrl(
        $app['config']['aws']['bucket'],
        $videoFile->getStoragePath(),
        '+1 hour'
    );

    $job = $zencoder->jobs->create([
        // options
        'region' => 'europe',
        'test' => $app['debug'],

        // reporting
        'grouping' => 'company-' . $videoFile->getCompany()->getId(),
        'pass_through' => json_encode([
            'type' => $app['em']->getClassMetadata(get_class($videoFile))->discriminatorValue,
            'company' => $videoFile->getCompany()->getId(),
            'videofile' => $videoFile->getId(),
        ]),

        // request
        'input' => $inputUrl,
        'outputs' => [
            [
                'type' => 'transfer-only',
                'skip' => ['max_duration' => 1],
            ],
        ],
    ]);

    $app['em']->transactional(function ($em) use ($videoFile, $job) {
        $videoFile->setStatus('working');
        $videoFile->setZencoderJobId($job->id);
    });

    $start = time();

    while (true) {
        sleep(5);

        $details = $zencoder->jobs->details($job->id);
        $input = $details->input;

        // success
        if ($input->state == 'finished') {
            $app['em']->transactional(function ($em) use ($videoFile, $input) {
                $videoFile->setStatus('complete');

                // container
                $videoFile->setDuration($input->duration_in_ms / 1000);
                $videoFile->setContainerFormat($input->format);
                $videoFile->setHeight($input->height);
                $videoFile->setWidth($input->width);
                $videoFile->setFrameRate($input->frame_rate);

                // video codec
                $videoFile->setVideoCodec($input->video_codec);
                $videoFile->setVideoBitRate($input->video_bitrate_in_kbps);

                // audio codec
                $videoFile->setAudioCodec($input->audio_codec);
                $videoFile->setAudioBitRate($input->audio_bitrate_in_kbps);
                $videoFile->setAudioSampleRate($input->audio_sample_rate);
                $videoFile->setAudioChannels((int) $input->channels);
            });

            break;
        }

        // error
        if ($input->state == 'failed') {
            $errorCode = $input->error_class;
            $errorMessage = $input->error_message;

            $app['em']->transactional(function ($em) use ($videoFile) {
                $videoFile->setStatus('error');
            });

            break;
        }

        // timeout
        if (time() - $start >= 90) {
            $zencoder->jobs->cancel($job->id);

            $app['em']->transactional(function ($em) use ($videoFile) {
                $videoFile->setStatus('error');
            });

            break;
        }
    }

    // response

    $groups = ['details', 'details.videos', 'details.inbounds'];
    return $app['single.response.json']($video, $groups);
})
->assert('video', '\d+')
->convert('video', 'converter.video:convert')
->assert('inbound', '\d+')
->convert('inbound', 'converter.inbound:convert')
;
