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

namespace CloudOutbound\YouPorn\Job;

use Cloud\Job\AbstractJob;
use Cloud\Model\TubesiteUser;
use Cloud\Model\VideoOutbound;
use CloudOutbound\Exception\AccountMismatchException;
use CloudOutbound\Exception\AccountStateException;
use CloudOutbound\Exception\LoginException;
use CloudOutbound\Exception\InternalInconsistencyException;
use CloudOutbound\Exception\UnexpectedResponseException;
use CloudOutbound\Exception\UnexpectedValueException;
use CloudOutbound\Exception\UploadException;
use CloudOutbound\YouPorn\HttpClient;
use GuzzleHttp\Post\PostFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * YouPorn Demo
 *
 * HD video requirements:
 *
 *  - aspect ratio of 16:9
 *  - higher than 720p and
 *  - bitrate higher than 4000kbps
 */
class DemoCombined extends AbstractJob
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * GuzzleHttp client for this request session. Holds a preconfigured client
     * instance which tracks header and cookie settings.
     *
     * @var HttpClient
     */
    protected $httpSession;

    /**
     * YouPorn forbidden strings in title and descriptions. Taken from their JS
     * on http://www.youporn.com/upload-legacy/
     *
     * @var array
     */
    protected $forbiddenStrings = ['>', ']', '[', '%', '/', '\\', 'http'];

    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('videooutbound', InputArgument::REQUIRED),
            ])
            ->setName('job:demo:youporn')
        ;
    }

    /**
     * Initializes the job
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->httpSession = new HttpClient([
            'base_url' => 'http://www.youporn.com/',
            'cookies' => [
                'is_pc'    => '1',
                'language' => 'en',
            ],
            'defaults' => [
                'debug' => $output->isVerbose(),
            ],
        ]);

        $this->em = $this->getHelper('em')->getEntityManager();
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outbound = $this->em->find('cx:videooutbound', $input->getArgument('videooutbound'));

        if (!$outbound) {
            throw new \InvalidArgumentException('No VideoOutbound with this ID');
        }

        // --

        if (!$outbound->getVideoFile()) {
            $output->write('<info>Encoding outbound video file ... </info>');
            $this->transcodeVideo($outbound);
            $output->writeln('<comment>' . $outbound->getVideoFile()->getStatus() . '</comment>');
            $output->writeln('');
        }

        // --

        $tubeuser = $outbound->getTubesiteUser();

        if (!$this->isLoggedIn($tubeuser)) {
            $this->login($tubeuser);
        }

        if ($outbound->getExternalId()) {

            $output->writeln('<info>VideoOutbound has externalId set, only refreshing status...</info>');
            $this->refreshVideoStatus($outbound);

        } else {

            $output->writeln('<info>Starting YouPorn outbound upload...</info>');

            $output->writeln('<info> + creating draft</info>');
            $this->createVideo($outbound);

            $output->writeln('<info> + uploading video file</info>');
            $this->uploadVideo($outbound);

            $output->writeln('<info> + submitting</info>');
            $this->submitVideo($outbound);
        }

        $this->logout($tubeuser);

        $output->writeln('<info>done.</info>');

        /*
         * TODO: refactor
         */
        //$output->writeln('');
        //$output->writeln('<info>Queueing refresh</info> ... in 10 seconds');
        //sleep(10);
        //\Resque::enqueue('default', get_called_class(), ['videooutbound' => $outbound->getId()]);
    }

    /**
     * Transcode and watermark the videofile
     *
     * @param VideoOutbound $outbound
     * @return void
     */
    protected function transcodeVideo(VideoOutbound $outbound)
    {
        $app = $this->getHelper('silex')->getApplication();

        $inboundVideoFile = $outbound
            ->getVideo()
            ->getInbounds()
            ->last()
            ->getVideoFile();

        // create outbound videofile

        $videoFile = new \Cloud\Model\VideoFile\OutboundVideoFile($outbound);
        $videoFile->setFilename($inboundVideoFile->getFilename());
        $videoFile->setFiletype('video/mp4');
        $videoFile->setStatus('pending');

        $app['em']->transactional(function ($em) use ($outbound, $videoFile) {
            $em->persist($videoFile);
        });

        $outbound->setVideoFile($videoFile);

        // transcode

        $s3 = $app['aws']->get('s3');
        $zencoder = $app['zencoder'];

        $inputUrl = $s3->getObjectUrl(
            $app['config']['aws']['bucket'],
            $inboundVideoFile->getStoragePath(),
            '+1 hour'
        );

        //$outputUrl = $s3->createPresignedUrl(
            //$s3->put($app['config']['aws']['bucket'] . '/' . $videoFile->getStoragePath()),
            //'+1 hour'
        //);

        $outputUrl = 's3://' . $app['config']['aws']['bucket'] . '/' . $videoFile->getStoragePath();

        $job = $zencoder->jobs->create([
            // options
            'region'  => 'europe',
            'private' => true,
            'test'    => $app['debug'],

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
                    'url' => $outputUrl,
                    'credentials' => 's3-cldsys-dev',

                    'format' => 'mp4',
                    'width' => 1280,
                    'height' => 720,

                    'audio_codec'   => 'aac',
                    'audio_quality' => 4,
                    'video_bitrate' => 4000,

                    'video_codec'   => 'h264',
                    'h264_profile'  => 'high',
                    'h264_level'    => 5.1,
                    'tuning'        => 'film',

                    'watermarks' => [
                        [
                            'url' => 'https://s3.amazonaws.com/cldsys-dev/static/watermarks/HDPOV-youporn.png',
                            'x' => 0,
                            'y' => 0,
                            'width' => 1280,
                            'height' => 720,
                        ]
                    ],
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
            $output = $details->outputs[0];

            // success
            if ($details->state == 'finished') {
                $app['em']->transactional(function ($em) use ($videoFile, $output) {
                    $videoFile->setStatus('complete');

                    // file
                    $videoFile->setFilesize($output->file_size_bytes);

                    // container
                    $videoFile->setDuration($output->duration_in_ms / 1000);
                    $videoFile->setContainerFormat($output->format);
                    $videoFile->setHeight($output->height);
                    $videoFile->setWidth($output->width);
                    $videoFile->setFrameRate($output->frame_rate);

                    // video codec
                    $videoFile->setVideoCodec($output->video_codec);
                    $videoFile->setVideoBitRate($output->video_bitrate_in_kbps);

                    // audio codec
                    $videoFile->setAudioCodec($output->audio_codec);
                    $videoFile->setAudioBitRate($output->audio_bitrate_in_kbps);
                    $videoFile->setAudioSampleRate($output->audio_sample_rate);
                    $videoFile->setAudioChannels((int) $output->channels);
                });

                break;
            }

            // error
            if ($details->state == 'failed') {
                $errorCode = $input->error_class;
                $errorMessage = $input->error_message;

                $app['em']->transactional(function ($em) use ($videoFile) {
                    $videoFile->setStatus('error');
                });

                break;
            }

            // timeout
            if (time() - $start >= 120) {
                $zencoder->jobs->cancel($job->id);

                $app['em']->transactional(function ($em) use ($videoFile) {
                    $videoFile->setStatus('error');
                });

                break;
            }
        }
    }

    /**
     * Checks if we are logged into the tubesite
     *
     * @return bool
     */
    protected function isLoggedIn(TubesiteUser $tubeuser)
    {
        $response = $this->httpSession->jsonGet('/change/user/data.json', [
            'headers' => [
                'X-Requested-With' => '',
            ],
        ]);

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $data = $response->json();

        // verify

        if (strtolower($data['username']) != strtolower($tubeuser->getUsername())) {
            throw new AccountMismatchException('YouPorn `username` does not match our username');
        }

        if (!$data['content_partner']) {
            throw new AccountStateException('YouPorn user is not a content partner account: `content_parter` not set');
        }

        return true;
    }

    /**
     * Log into the tubesite
     *
     * @return void
     */
    protected function login(TubesiteUser $tubeuser)
    {
        $response = $this->httpSession->post('/login/', [
            'headers' => [
                'Referer' => 'http://www.youporn.com/login/',
            ],
            'body' => [
                'login[username]' => $tubeuser->getUsername(),
                'login[password]' => $tubeuser->getPassword(),
                'login[previous]' => '/upload/',
                'login[local_data]' => '{}',
            ],
        ]);

        // success

        if ($response->getStatusCode() == 302) {
            if (!$this->isLoggedIn($tubeuser)) {
                throw new InternalInconsistencyException('Login post succeeded but still no access');
            }
            return;
        }

        // error with html message

        if ($response->getStatusCode() == 200) {
            $dom = new DomCrawler();
            $dom->addHtmlContent((string) $response->getBody());

            try {
                $message = $dom->filter('.loginForm .errorRed')->text();
            } catch (\InvalidArgumentException $e) {
                $message = 'unknown error; could not extract error from response body';
            }

            throw new LoginException('Login failed: ' . $message);
        }

        // other error

        throw new UnexpectedResponseException(sprintf(
            'Login failed: server error; (%d) %s',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
    }

    /**
     * Log out from the tubesite
     *
     * For YouPorn, this is not really needed because they're working with the
     * `login` signed cookie.
     *
     * @return void
     */
    protected function logout(TubesiteUser $tubeuser)
    {
        $response = $this->httpSession->get('/logout/', [
            'headers' => [
                'Referer' => 'http://www.youporn.com/upload/',
            ],
        ]);

        if ($response->getStatusCode() != 302) {
            throw new UnexpectedResponseException(sprintf(
                'Logout failed: server error; (%d) %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        $cookieJar = $this->httpSession->getCookieJar();

        $cookieJar->clear('.youporn.com', '/', 'sid');
        $cookieJar->clear('.youporn.com', '/', 'login');
    }

    /**
     * Refreshes the remote video status
     *
     *  - `uploaded` ... Uploaded your video. Thumbnails are being created...
     *  - `encoding_in_progress` ...
     *  - `encoding_failure` ...
     *  - `waiting_for_approval` ... Encoding complete! Preview your video here
     *  - `refused` ... check review_note, e.g. Upload cancelled by the user
     *  - `released` ... Your video is live at ...
     *
     * @param VideoOutbound $outbound
     * @return void
     */
    protected function refreshVideoStatus(VideoOutbound $outbound)
    {
        $response = $this->httpSession->jsonGet(
            ['/upload/video-status/{id}/', ['id' => $outbound->getExternalId()]]
        );

        $data = $response->json()['video'];

        // verify

        if ($data['video_id'] != $outbound->getExternalId()) {
            throw new InternalInconsistencyException('YouPorn `video_id` does not match our external id');
        }

        // persist

        $this->em->transactional(function ($em) use ($outbound, $data) {
            $params = array_intersect_key($data, array_flip([
                'status', 'comment', 'review_note',
            ]));

            $outbound->addParams($params);

            switch($data['status']) {
                case 'uploaded':
                case 'encoding_in_progress':
                    $outbound->setStatus(VideoOutbound::STATUS_WORKING);
                    break;

                case 'waiting_for_approval':
                case 'released':
                    $outbound->setStatus(VideoOutbound::STATUS_COMPLETE);
                    break;

                case 'refused':
                    $outbound->setStatus(VideoOutbound::STATUS_ERROR);
                    break;

                default:
                    throw new UnexpectedValueException(sprintf(
                        'Unexpected status `%s` for outbound `%d`',
                        $data['status'], $outbound->getId()
                    ));
                    break;
            }
        });

        return $data['status'];
    }

    /**
     * Create a remote draft
     */
    protected function createVideo(VideoOutbound $outbound)
    {
        $this->em->transactional(function ($em) use ($outbound) {
            $outbound->setStatus(VideoOutbound::STATUS_WORKING);
        });

        $videoFile = $outbound
            ->getVideo()
            ->getInbounds()
            ->last()
            ->getVideoFile();

        $response = $this->httpSession->jsonPost('/upload/create-videos/', [
            'body' => [
                'file' => $videoFile->getFilename(), // just the filename here
            ],
        ]);

        $data = $response->json();

        $this->em->transactional(function ($em) use ($outbound, $data) {
            $outbound->setExternalId($data['video_id']);
            $outbound->setParam('video_id', $data['video_id']);
            $outbound->setParam('user_uploader_id', $data['user_uploader_id']);
        });
    }

    /**
     * Upload a video file to the remote server
     */
    protected function uploadVideo(VideoOutbound $outbound)
    {
        $videoFile = $outbound
            ->getVideo()
            ->getInbounds()
            ->last()
            ->getVideoFile();

        // s3 object  TODO: refactor

        $app = $this->getHelper('silex')->getApplication();
        $s3  = $app['aws']->get('s3');

        $video = $outbound->getVideo();

        $object = $s3->getObject([
            'Bucket' => $app['config']['aws']['bucket'],
            'Key'    => $videoFile->getStoragePath(),
        ]);

        $stream = $object['Body']->getStream();

        // upload

        $request = $this->httpSession->createJsonRequest('POST', '/upload/');

        $request->getBody()
            ->setField('userId', $outbound->getParam('user_uploader_id'))
            ->setField('videoId', $outbound->getExternalId())
            ->addFile(new PostFile(
                'files[]',
                $stream,
                $videoFile->getFilename(),
                ['Content-Type' => $videoFile->getFiletype()]
            ));

        $response = $this->httpSession->send($request);

        $data = $response->json()[0];

        // error

        if (!$data['success']) {
            throw new UploadException('YouPorn file upload failed: ' . (string) $response->getBody());
        }

        // verify

        if ($data['size'] != $videoFile->getFilesize()) {
            throw new InternalInconsistencyException('YouPorn `size` does not match our filesize');
        }
    }

    /**
     * Post metadata and submit remote video for review
     */
    protected function submitVideo(VideoOutbound $outbound)
    {
        $app      = $this->getHelper('silex')->getApplication();

        $video    = $outbound->getVideo();
        $tubeuser = $outbound->getTubesiteUser();

        $disableComments = $tubeuser->getParam('video_options_disable_commenting', null);

        if ($disableComments === true) {
            $disableComments = '1';
        } elseif ($disableComments === false) {
            $disableComments = '0';
        } else {
            $disableComments = '';
        }

        $response = $this->httpSession->jsonPost(
            ['/change/video/{id}/', ['id' => $outbound->getExternalId()]],
            [
                'body' => [
                    // TODO: refactor
                    'videoedit[title]' =>
                        substr(($app['debug'] ? '(TEST) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getTitle()), 0, 250),

                    'videoedit[description]' =>
                        substr(($app['debug'] ? '(TEST ONLY - PLEASE REJECT) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getDescription()), 0, 2000),

                    // TODO: pull from video tags
                    'videoedit[uploader_category_id]' => '36',
                    'videoedit[orientation]' => 'straight',
                    'videoedit[tags]' => '',
                    'videoedit[pornstars]' => '',

                    'videoedit[content_partner_site_id]' =>
                        $tubeuser->getParam('content_partner_site_id'),

                    'videoedit[video_options_disable_commenting]' => '0', // $disableComments,
                ],
            ]
        );

        $data = $response->json();

        // verify

        if (!$data['success']) {
            throw new UploadException(sprintf(
                'YouPorn metadata post and video submit failed: %s',
                json_encode($data)
            ));
        }
    }
}

