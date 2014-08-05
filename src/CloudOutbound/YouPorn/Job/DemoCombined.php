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

use InvalidArgumentException;
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
use CloudOutbound\YouPorn\CategoryMapper;
use CloudOutbound\YouPorn\HttpClient;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        /** @var $outbound VideoOutbound */
        $outbound = $this->em->find('cx:videoOutbound', $input->getArgument('videooutbound'));

        if (!$outbound) {
            throw new \InvalidArgumentException('No VideoOutbound with this ID');
        }

        try {

        // --

        if (!$outbound->getVideoFile()) {
            $output->write('<info>Encoding outbound video file ... </info>');
            $this->transcodeVideo($outbound);
            $output->writeln('<comment>' . $outbound->getVideoFile()->getStatus() . '</comment>');
            $output->writeln('');
        }

        // --

        $tubeuser = $outbound->getTubesiteUser();

        if ($outbound->getStatus() == 'error') {

            $output->writeln('<error>VideoOutbound has error, skipping ... </error>');

        } elseif ($outbound->getExternalId()) {

            if (!$this->isLoggedIn($tubeuser)) {
                $this->login($tubeuser);
            }

            $output->write('<info>VideoOutbound has externalId set, only refreshing status ... </info>');
            $this->refreshVideoStatus($outbound);
            $output->writeln('<comment>' . $outbound->getStatus() . '</comment>');

            $this->logout($tubeuser);

        } else {

            if (!$this->isLoggedIn($tubeuser)) {
                $this->login($tubeuser);
            }

            /*
            $output->writeln('<info>Starting YouPorn outbound upload...</info>');

            $output->writeln('<info> + creating draft</info>');
            $this->createVideo($outbound);

            $output->writeln('<info> + uploading video file</info>');
            $this->uploadVideo($outbound);

            $output->writeln('<info> + submitting</info>');
            $this->submitVideo($outbound);
            */

            // --

            $output->writeln('<info>Starting YouPorn <comment>Legacy Form</comment> outbound upload...</info>');

            $this->em->transactional(function ($em) use ($outbound) {
                $outbound->setStatus('working');
            });

            $output->writeln('<info> + validating metadata</info>');
            $this->legacyValidateVideo($outbound);

            $output->writeln('<info> + uploading video and metadata</info>');
            $this->legacyUploadVideo($outbound);

            sleep(5);

            $output->write('<info> + refreshing status ... </info>');
            $this->refreshVideoStatus($outbound);
            $output->writeln('<comment>' . $outbound->getStatus() . '</comment>');

            $this->logout($tubeuser);

            $output->writeln('<info>done.</info>');
        }

        /*
         * TODO: refactor
         */
        //$output->writeln('');
        //$output->writeln('<info>Queueing refresh</info> ... in 10 seconds');
        //sleep(10);
        //\Resque::enqueue('default', get_called_class(), ['videooutbound' => $outbound->getId()]);

        } catch (\Exception $e) {
            $this->em->transactional(function ($em) use ($outbound) {
                $outbound->setStatus('error');
            });

            throw $e;
        }
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
        $videoFile->setFilename(pathinfo($inboundVideoFile->getFilename())['filename'] . '.mp4');
        $videoFile->setFiletype('video/mp4');
        $videoFile->setStatus('pending');

        $app['em']->transactional(function ($em) use ($outbound, $videoFile) {
            $em->persist($videoFile);
            $outbound->setVideoFile($videoFile);
        });

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

        // ---

        $targetWidth = $inboundVideoFile->getWidth();
        $targetHeight = $inboundVideoFile->getHeight();

        $originalWidth = 1280;
        $originalHeight = 720;

        $heightFactor = $targetHeight / $originalHeight;
        $widthFactor = $targetWidth / $originalWidth;

        $factor = min($heightFactor, $widthFactor);

        $targetHeight = floor($originalHeight * $factor);
        $targetWidth = floor($originalWidth * $factor);

        // ---

        $watermarks = [];
        if ($outbound->getVideo()->getSite()->getSlug() == 'hdpov') {
            // RU: HDPOV
            $watermarks[] = [
                'url' => 'https://s3.amazonaws.com/cldsys-dev/static/watermarks/HDPOV-youporn.png',
                'x' => '-0', 'y' => '-0', 'width' => $targetWidth, 'height' => $targetHeight,
            ];
        }
        if ($outbound->getVideo()->getSite()->getSlug() == 'sexfromrussia') {
            // PornNerd: Sex From Russia
            $watermarks[] = [
                'url' => 'https://s3.amazonaws.com/cldsys-dev/static/watermarks/sexfromrussia-youporn.png',
                'x' => '-0', 'y' => '-0', 'width' => $targetWidth, 'height' => $targetHeight,
            ];
        }
        if ($outbound->getVideo()->getSite()->getSlug() == 'suckonitbaby') {
            // PornNerd: Suck On It Baby
            $watermarks[] = [
                'url' => 'https://s3.amazonaws.com/cldsys-dev/static/watermarks/suckonitbaby-youporn.png',
                'x' => '-0', 'y' => '-0', 'width' => $targetWidth, 'height' => $targetHeight,
            ];
        }

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
                    'credentials' => $app['debug'] ? 's3-cldsys-dev' : 's3-cldsys-prod',

                    'format' => 'mp4',
                    'width' => 1280,
                    'height' => 720,

                    'audio_codec'   => 'aac',
                    'audio_quality' => 4,
                    'max_video_bitrate' => 4000,
                    'max_frame_rate' => 30,

                    'video_codec'   => 'h264',
                    'h264_profile'  => 'high',
                    'h264_level'    => 5.1,
                    'tuning'        => 'film',

                    'watermarks' => $watermarks,
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

            // error
            if ($details->state == 'failed') {
                $errorCode = $details->error_class;
                $errorMessage = $details->error_message;

                $app['em']->transactional(function ($em) use ($videoFile) {
                    $videoFile->setStatus('error');
                });

                break;
            }

            // transfer error
            if ($details->state == 'finished'
                && isset($details->backup_server_used)
                && $details->backup_server_used
            ) {
                $errorCode = $details->primary_upload_error_link;
                $errorMessage = $details->primary_upload_error_message;

                $app['em']->transactional(function ($em) use ($videoFile) {
                    $videoFile->setStatus('error');
                });

                break;
            }

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

            // timeout
            if (time() - $start >= 900) {
                $zencoder->jobs->cancel($job->id);

                $app['em']->transactional(function ($em) use ($videoFile) {
                    $videoFile->setStatus('error');
                });

                break;
            }
        }

        if ($videoFile->getStatus() != 'complete') {
            throw new \Exception('Failed to encode outbound video file');
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
                    $outbound->setStatus(VideoOutbound::STATUS_WORKING);
                    break;

                case 'encoding_in_progress':
                case 'waiting_for_approval':
                case 'released':
                    $outbound->setStatus(VideoOutbound::STATUS_COMPLETE);
                    break;

                case 'encoding_failure':
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

        $videoFile = $outbound->getVideoFile();

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
        $videoFile = $outbound->getVideoFile();

        // s3 object  TODO: refactor

        $app = $this->getHelper('silex')->getApplication();
        $s3  = $app['aws']->get('s3');

        $video = $outbound->getVideo();

        $objectUrl = $s3->getObjectUrl(
            $app['config']['aws']['bucket'],
            $videoFile->getStoragePath(),
            '+1 hour'
        );

        $stream = Stream::factory(
            fopen($objectUrl, 'r', false),
            $videoFile->getFilesize()
        );

        // upload

        $request = $this->httpSession->createJsonRequest(
            'POST',
            '/upload/',
            [
                'timeout' => 900,
            ]
        );

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

        $mapper   = new CategoryMapper();

        $disableComments = $tubeuser->getParam('video_options_disable_commenting', null);

        if ($disableComments === true) {
            $disableComments = '1';
        } elseif ($disableComments === false) {
            $disableComments = '0';
        } else {
            $disableComments = '';
        }

        // categories

        $category = $mapper->convert($video->getPrimaryCategory());

        $tags = $video
            ->getAllCategories()
            ->map(function ($d) { return $d->getSlug(); })
            ->toArray();

        $tags = implode(',', $tags);

        // request

        $response = $this->httpSession->jsonPost(
            ['/change/video/{id}/', ['id' => $outbound->getExternalId()]],
            [
                'body' => [
                    // TODO: refactor
                    'videoedit[title]' =>
                        substr(($app['debug'] ? '(TEST) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getTitle()), 0, 250),

                    'videoedit[description]' =>
                        substr(($app['debug'] ? '(TEST ONLY - PLEASE REJECT) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getDescription()), 0, 2000),

                    'videoedit[uploader_category_id]' => $category,
                    'videoedit[orientation]' => 'straight',
                    'videoedit[tags]' => $tags,
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

    //////////////////////////////////////////////////////////////////////////

    /**
     * Validate metadata via legacy HTML form
     */
    protected function legacyValidateVideo(VideoOutbound $outbound)
    {
        $app   = $this->getHelper('silex')->getApplication();
        $video = $outbound->getVideo();

        // categories

        $tags = $video
            ->getAllCategories()
            ->map(function ($d) { return $d->getSlug(); })
            ->toArray();

        $tags = implode(',', $tags);

        // request

        $response = $this->httpSession->jsonPost('/upload/word_validation/', [
            'headers' => [
                'Referer' => 'http://www.youporn.com/upload-legacy/',
                'Origin'  => 'http://www.youporn.com',
            ],
            'body' => [
                'title' =>
                    substr(($app['debug'] ? '(TEST) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getTitle()), 0, 250),
                'description' =>
                    substr(($app['debug'] ? '(TEST ONLY - PLEASE REJECT) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getDescription()), 0, 2000),
                'tags' => $tags,
            ],
        ]);

        $body = (string) $response->getBody();

        // success

        if ($body == 'false') {
            return;
        }

        // error

        throw new UploadException(
            'YouPorn legacy metadata validation failed: '
            . 'Invalid fields: ' . $body
        );
    }

    /**
     * Upload video and post metadata via legacy HTML form
     *
     * This can be used in place of `uploadVideo()` and `submitVideo()`
     */
    protected function legacyUploadVideo(VideoOutbound $outbound)
    {
        $video     = $outbound->getVideo();
        $videoFile = $outbound->getVideoFile();
        $tubeuser  = $outbound->getTubesiteUser();

        // s3 object  TODO: refactor

        $app = $this->getHelper('silex')->getApplication();
        $s3  = $app['aws']->get('s3');

        $objectUrl = $s3->getObjectUrl(
            $app['config']['aws']['bucket'],
            $videoFile->getStoragePath(),
            '+1 hour'
        );

        $stream = Stream::factory(
            fopen($objectUrl, 'r', false),
            $videoFile->getFilesize()
        );

        // categories

        $mapper = new CategoryMapper();

        $category = $mapper->convert($video->getPrimaryCategory());

        $tags = $video
            ->getAllCategories()
            ->map(function ($d) { return $d->getSlug(); })
            ->toArray();

        $tags = implode(',', $tags);

        // upload

        $request = $this->httpSession->createRequest(
            'POST',
            '/upload-legacy/',
            [
                'timeout' => 900,
                'headers' => [
                    'Referer' => 'http://www.youporn.com/upload-legacy/',
                    'Origin'  => 'http://www.youporn.com',
                ],
                'query' => [
                    'content_partner_site_id' => $tubeuser->getParam('content_partner_site_id'),
                ],
                'body' => [
                    'upload[title]' =>
                        substr(($app['debug'] ? '(TEST) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getTitle()), 0, 250),

                    'upload[description]' =>
                        substr(($app['debug'] ? '(TEST ONLY - PLEASE REJECT) ' : '') . str_replace($this->forbiddenStrings, ' ', $video->getDescription()), 0, 2000),

                    'upload[uploader_category_id]' => $category,
                    'upload[type]' => 'straight',
                    'upload[tags]' => $tags,
                ],
            ]
        );

        $request->getBody()
            ->addFile(new PostFile(
                'upload[up_file]',
                $stream,
                $videoFile->getFilename(),
                ['Content-Type' => $videoFile->getFiletype()]
            ));

        $response = $this->httpSession->send($request);

        $body = (string) $response->getBody();

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        // error

        if (strpos($body, '<h1>Upload Complete!</h1>') === false) {
            try {
                $error = $dom->filter('.form_row ul.error li')->text();
            } catch (InvalidArgumentException $e) {
                $error = 'unknown error; could not extract error from response body';
            }

            throw new UploadException('YouPorn legacy upload failed: ' . $error);
        }

        $receiptRows = $dom->filter('.uploadReceipt tr');

        if (!$receiptRows->count()) {
            throw new UnexpectedResponseException('Could not extract upload receipt from response body');
        }

        // verify

        $data = $receiptRows->each(function ($node) {
            try {
                $key = $node->filter('th')->text();
                $value = $node->filter('td')->text();

                $key = trim(rtrim(strtolower($key), ':'));
                $value = trim($value);

                return [$key => $value];
            } catch (InvalidArgumentException $e) {
                return [];
            }
        });

        $data = array_reduce($data, 'array_replace', []); // flatten

        //var_dump($data);

        if (empty($data['file size'])) {
            throw new UnexpectedResponseException('Could not extract upload file size from response body');
        }

        $size = ((float) $data['file size']) * 1000;

        if ($size != $videoFile->getFilesize()) {
            throw new InternalInconsistencyException('YouPorn legacy uploaded file size does not match our filesize');
        }

        if (empty($data['video id']) || !is_numeric($data['video id'])) {
            throw new UnexpectedResponseException('Could not extract video id from response body');
        }

        // success

        $this->em->transactional(function ($em) use ($outbound, $data) {
            $outbound->setExternalId((int) $data['video id']);
            $outbound->setParam('legacy', true);
        });
    }
}

