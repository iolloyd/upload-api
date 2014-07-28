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

namespace CloudOutbound\XVideos\Job;

use InvalidArgumentException;
use Cloud\Job\AbstractJob;
use Cloud\Model\TubesiteUser;
use Cloud\Model\VideoOutbound;
use CloudOutbound\Exception\AccountLockedException;
use CloudOutbound\Exception\AccountMismatchException;
use CloudOutbound\Exception\AccountStateException;
use CloudOutbound\Exception\InternalInconsistencyException;
use CloudOutbound\Exception\LoginException;
use CloudOutbound\Exception\UnexpectedResponseException;
use CloudOutbound\Exception\UnexpectedValueException;
use CloudOutbound\Exception\UploadException;
use CloudOutbound\XHamster\HttpClient;
use GuzzleHttp\Url;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Post\PostFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * XVideos Demo
 *
 * - title: 80 chars
 * - keywords: Enter keywords separated by a space. Use only letters, figures and dashes. 20 keywords maximum.
 */
class DemoCombined extends AbstractJob
{
    const STATUS_QUEUED = 'Queued for encoding';
    const STATUS_ENCODING = 'Encoding process';
    const STATUS_ONLINE = 'Online';

    /**
     * @var \Monolog\Logger
     */
    protected $log;

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
    protected $forbiddenStrings = ['<', '>'];

    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('videooutbound', InputArgument::REQUIRED),
            ])
            ->setName('job:demo:xvideos')
        ;
    }

    /**
     * Initializes the job
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getHelper('silex')->getApplication();

        $this->httpSession = new HttpClient([
            'base_url' => 'http://upload.xvideos.com/',
            'cookies' => [
            ],
            'defaults' => [
                'debug' => $output->isVerbose(),
            ],
        ]);

        $this->em  = $app['em'];
        $this->log = $app['monolog']('worker.outbound');
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outbound = $this->em->find('cx:videoOutbound', $input->getArgument('videooutbound'));

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

            if (!$this->isLoggedIn($tubeuser)) {
                throw new InternalInconsistencyException('Login succeeded but still no access');
            }
        }

        // $sites = $this->getCppSites(); var_dump($sites); exit;

        //exit;

        if ($outbound->getExternalId()) {

            $output->write('<info>VideoOutbound has externalId set, only refreshing status ... </info>');
            $this->refreshVideoStatus($outbound);
            $output->writeln('<comment>' . $outbound->getStatus() . '</comment>');

        } else {

            $output->writeln('<info>Starting <comment>XVideos</comment> outbound upload...</info>');

            $output->writeln('<info> + validating video</info>');
            $this->validateVideo($outbound);

            $output->writeln('<info> + preparing upload</info>');
            $this->prepareVideo($outbound);

            $output->writeln('<info> + uploading video</info>');
            try {
                $this->uploadVideo($outbound);
            } catch (\GuzzleHttp\Exception\AdapterException $e) {
                // try again
                $output->writeln(sprintf('<info> +</info> <error>failed: %s</error>', $e->getMessage()));
                $output->writeln('<info> + retrying...</info>');
                $this->prepareVideo($outbound);
                $this->uploadVideo($outbound);
            }

            $this->refreshVideoStatus($outbound);

            $output->writeln('<info> + saving metadata</info>');
            $this->submitVideoMetadata($outbound);

            $this->refreshVideoStatus($outbound);
        }

        $this->logout($tubeuser);

        $output->writeln('<info>done.</info>');
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
                    'force_aac_profile' => 'aac-lc',

                    'video_bitrate' => 5000,
                    'max_frame_rate' => 30,

                    'video_codec'   => 'h264',
                    'h264_profile'  => 'high',
                    'h264_level'    => 5.1,
                    'tuning'        => 'film',

                    'speed' => 5,

                    'watermarks' => [
                        [
                            'url' => 'https://s3.amazonaws.com/cldsys-dev/static/watermarks/HDPOV-generic.png',
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
            if (time() - $start >= 900) {
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
        $response = $this->httpSession->get('/account');

        if ($response->getStatusCode() != 200) {
            throw new UnexpectedResponseException(sprintf(
                'Failed to fetch account details: server error; (%d) %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        $body = (string) $response->getBody();

        // check

        if (strpos($body, '<h2>Welcome to your xvideos user panel</h2>') === false) {
            $this->log->debug('Not logged in; no H2 banner element in response');
            return false;
        }

        // verify

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        try {
            $accountType = $dom
                ->filter('.pageAccount #content .adminBox .blackTitle + h4')
                ->filter('strong')
                ->text();
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedResponseException('Could not extract account type from response body');
        }

        $this->log->debug('Account type: ' . $accountType);

        if ($accountType != 'professional') {
            throw new AccountStateException('XVideos user is not a content partner account; got ' . $accountType);
        }

        if (stripos($body, $tubeuser->getUsername()) === false) {
            throw new AccountMismatchException('XVideos response does not contain expected username');
        }

        $cookie = $this->getCookie('HEXAVID_LOGIN');
        list($username, $uid, $site, ) = explode('|', urldecode($cookie));
        $username = urldecode($username);

        if (strtolower($username) != strtolower($tubeuser->getUsername())) {
            throw new AccountMismatchException('XVideos cookie does not match our username');
        }

        if ($tubeuser->getExternalId() && $uid != $tubeuser->getExternalId()) {
            throw new AccountMismatchException('XVideos cookie does not match our external ID');
        }

        // persist

        $this->em->transactional(function ($em) use ($tubeuser, $site) {
            $tubeuser->setParam('site', $site);
        });

        $this->log->debug('Logged in as: {username}', compact('username', 'uid', 'site'));

        return true;
    }

    /**
     * Log into the tubesite
     *
     * @return void
     */
    protected function login(TubesiteUser $tubeuser)
    {
        $response = $this->httpSession->post('/account', [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account',
            ],
            'body' => [
                'referer'  => 'http://www.xvideos.com/',
                'login'    => $tubeuser->getUsername(),
                'password' => $tubeuser->getPassword(),
                'log'      => 'Login to your account',
            ],
        ]);

        // success

        if ($response->getStatusCode() == 302) {
            return;
        }

        // error with html message

        if ($response->getStatusCode() == 200) {
            $dom = new DomCrawler();
            $dom->addHtmlContent((string) $response->getBody());

            try {
                $message = $dom->filter('.form_global_error')->text();
            } catch (InvalidArgumentException $e) {
                try {
                    $message = $dom->filter('.formLine.error .inlineError')->text();
                } catch (InvalidArgumentException $e) {
                    $message = 'unknown error; could not extract error from response body';
                }
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
     * @return void
     */
    protected function logout(TubesiteUser $tubeuser)
    {
        $response = $this->httpSession->get('/account/signout', [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account',
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
        $cookieJar->clear('.xvideos.com', '/', 'HEXAVID_LOGIN');
    }

    /**
     * Create Video: Validate
     */
    protected function validateVideo(VideoOutbound $outbound)
    {
        $this->em->transactional(function ($em) use ($outbound) {
            $outbound->setStatus(VideoOutbound::STATUS_WORKING);
        });

        $videoFile = $outbound->getVideoFile();

        $response = $this->httpSession->get('/account/title_blacklist/', [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account/uploads/new',
            ],
            'query' => [
                'title' => 'C:\fakepath\\' . $videoFile->getFilename(),
            ],
        ]);

        $body = (string) $response->getBody();

        // success

        if ($body == 'TITLE_IS_OK') {
            return;
        }

        // error

        throw new UploadException('XVideos file validation failed: ' . $body);
    }

    /**
     * Create Video: Prepare
     */
    protected function prepareVideo(VideoOutbound $outbound)
    {
        $response = $this->httpSession->get('/account/uploads/new', [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account',
            ],
        ]);

        $body = (string) $response->getBody();

        // extract

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        try {
            $progressKey = $dom->filter('#upload_form_basic input[name="APC_UPLOAD_PROGRESS"]')->attr('value');
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedResponseException('Could not extract upload form from response body');
        }

        $this->log->debug('APC_UPLOAD_PROGRESS = ' . $progressKey);

        // success

        $this->em->transactional(function ($em) use ($outbound, $progressKey) {
            $outbound->setParam('APC_UPLOAD_PROGRESS', $progressKey);
        });
    }

    /**
     * Create Video: Upload
     */
    protected function uploadVideo(VideoOutbound $outbound)
    {
        $app = $this->getHelper('silex')->getApplication();
        $s3  = $app['aws']->get('s3');

        $videoFile = $outbound->getVideoFile();

        $objectUrl = $s3->getObjectUrl(
            $app['config']['aws']['bucket'],
            $videoFile->getStoragePath(),
            '+1 hour'
        );

        $stream = \GuzzleHttp\Stream\Stream::factory(
            fopen($objectUrl, 'r', false),
            $videoFile->getFilesize()
        );

        /*
         * Upload
         */

        $request = $this->httpSession->createRequest(
            'POST',
            '/account/uploads/submit',
            [
                'timeout' => 900,
                'headers' => [
                    'Referer' => 'http://upload.xvideos.com/account/uploads/new',
                    'Origin' => 'http://upload.xvideos.com',
                ],
                'query' => [
                    'video_type' => 'other', // gay, shemale, other
                ],
                'body' => [
                    'APC_UPLOAD_PROGRESS' => $outbound->getParam('APC_UPLOAD_PROGRESS'),
                    'message' => $app['debug'] ? 'TEST - PLEASE REJECT' : '',
                ],
            ]
        );

        $request->getBody()
            ->addFile(new PostFile(
                'upload_file',
                $stream,
                $videoFile->getFilename(),
                ['Content-Type' => $videoFile->getFiletype()]
            ));

        $response = $this->httpSession->send($request);

        //var_dump($response); print((string) $response->getBody());

        // error

        if ($response->getStatusCode() != 302) {
            throw new UnexpectedResponseException(sprintf(
                'XVideos upload failed: server error; (%d) %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        $body = (string) $response->getBody();

        if (strpos($body, 'Upload saved.') === false) {
            throw new UnexpectedResponseException('XVideos upload failed: invalid response; ' . $body);
        }

        /*
         * Validate Progress
         */

        $progressResponse = $this->httpSession->get('/account/uploads/progress', [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account/uploads/new',
            ],
            'query' => [
                'upload_id' => $outbound->getParam('APC_UPLOAD_PROGRESS'),
                'basic_upload' => '1',
            ],
        ]);

        //var_dump($progressResponse); print((string) $progressResponse->getBody());

        $data = $progressResponse->json();

        // error

        if ($data['done'] != 1) {
            throw new InternalInconsistencyException('XVideos upload failed: progress response not done');
        }

        if ($data['current'] < $videoFile->getFilesize()) {
            throw new InternalInconsistencyException('XVideos uploaded size is less than our filesize');
        }

        // success

        $commitUrl = $response->getHeader('Location');

        /*
         * Commit Upload
         */

        $commitResponse = $this->httpSession->get($commitUrl, [
            'headers' => [
                'Referer' => 'http://upload.xvideos.com/account/uploads/new',
            ],
        ]);

        //var_dump($commitResponse); print((string) $commitResponse->getBody());

        $body = (string) $commitResponse->getBody();

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        // error

        if (strpos($body, 'Uploaded video saved !') === false) {
            try {
                $error = $dom->filter('#upload_commit_results li .inlineError')->text();
            } catch (InvalidArgumentException $e) {
                $error = 'unknown error; could not extract error from response body';
            }

            throw new UploadException('XVideos upload failed: ' . $error);
        }

        // success

        try {
            $link = $dom->filter('#upload_commit_results li a[target="_top"]')->attr('href');
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedResponseException('Could not extract video link from response body');
        }

        if (!preg_match('|^/account/uploads/(?P<id>\d+)/edit$|', $link, $matches)) {
            throw new UnexpectedResponseException('Could not extract external id from video link; ' . $link);
        }

        $this->log->debug('external id = {externalId}', ['externalId' => $matches['id']]);

        $this->em->transactional(function ($em) use ($outbound, $matches) {
            $outbound->setExternalId($matches['id']);
        });
    }

    /**
     * Create Video: Metadata
     */
    protected function submitVideoMetadata(VideoOutbound $outbound)
    {
        $app      = $this->getHelper('silex')->getApplication();
        $tubeuser = $outbound->getTubesiteUser();
        $video    = $outbound->getVideo();

        $response = $this->httpSession->post(
            ['/account/uploads/{id}/edit', ['id' => $outbound->getExternalId()]],
            [
                'body' => [
                    'hide' => $app['debug'] ? '2' : '0',

                    // TODO: refactor
                    'title' =>
                        substr(str_replace($this->forbiddenStrings, ' ', ($app['debug'] ? 'TEST - ' : '') . $video->getTitle()), 0, 60),
                    'description' =>
                        substr(str_replace($this->forbiddenStrings, ' ', ($app['debug'] ? 'TEST PLEASE REJECT - ' : '') . $video->getDescription()), 0, 500),

                    // TODO:
                    'keywords' => 'pov',

                    'channel' => $tubeuser->getParam('channel'),
                    'update_video_information' => 'Update information',
                ],
            ]
        );

        // success

        if ($response->getStatusCode() == 302) {
            return;
        }

        // error

        $body = (string) $response->getBody();

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        try {
            $error = $dom->filter('.contentBox .inlineError')->text();
        } catch (InvalidArgumentException $e) {
            $error = 'unknown error; could not extract error from response body';
        }

        throw new UploadException('XVideos upload failed: ' . $error);
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
        $response = $this->httpSession->get(
            ['/account/uploads/{id}/edit', ['id' => $outbound->getExternalId()]]
        );

        $body = (string) $response->getBody();

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        $formLines = $dom->filter('#video-edit-form .formLine');

        // error

        if (!$formLines->count()) {
            throw new UnexpectedResponseException('Could not extract video data from response body');
        }

        // success

        $data = $formLines->each(function ($node) {
            try {
                $label = $node->filter('label')->text();
                $content = $node->filter('.content')->text();

                $label = trim(rtrim(strtolower($label), ':'));
                $content = trim($content);

                return [$label => $content];
            } catch (InvalidArgumentException $e) {
            }
        });

        $data = array_reduce($data, 'array_replace', []); // flatten

        $this->log->debug('video status = ' . $data['status']);

        // persist

        $this->em->transactional(function ($em) use ($outbound, $data) {
            $params = array_intersect_key($data, array_flip([
                'status',
            ]));

            $outbound->addParams($params);

            switch ($data['status']) {
                case self::STATUS_QUEUED:
                case self::STATUS_ENCODING:
                    $outbound->setStatus(VideoOutbound::STATUS_WORKING);
                    break;

                case self::STATUS_ONLINE:
                    $outbound->setStatus(VideoOutbound::STATUS_COMPLETE);
                    break;

                default:
                    throw new UnexpectedValueException(sprintf(
                        'Unexpected status `%s` for outbound `%d`',
                        $data['status'], $outbound->getId()
                    ));
                    break;
            }
        });
    }

    /**
     * Adds a session cookie to the cookie jar
     *
     * @param  string $domain
     * @param  string $name
     * @param  string $value
     * @return void
     */
    protected function setCookie($domain, $name, $value)
    {
        $cookieJar = $this->httpSession->getCookieJar();
        $cookieJar->setCookie(new SetCookie([
            'Domain'  => $domain,
            'Name'    => $name,
            'Value'   => $value,
            'Discard' => true
        ]));
    }

    /**
     * Gets a cookie value from the cookie jar
     *
     * @param  string $name
     * @return mixed|null
     */
    protected function getCookie($name)
    {
        $cookieJar = $this->httpSession->getCookieJar();

        foreach ($cookieJar as $cookie) {
            if ($cookie->getName() == $name) {
                return $cookie->getValue();
            }
        }

        return null;
    }
}

