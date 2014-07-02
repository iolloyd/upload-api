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

namespace CloudOutbound\XHamster\Job;

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
 * xHamster Demo
 *
 *  + extensions: wma|wmv|mpg3|mpg|mpeg|avi|mov|flv|mp4|3gp|asf
 *  + uses http://sourceforge.net/projects/uber-uploader/
 *  + maximum 3 delete video calls per day
 */
class DemoCombined extends AbstractJob
{
    const STATUS_NOT_CONVERTED = 'Not converted';
    const STATUS_REPOST        = 'Repost';
    const STATUS_PUBLICATION   = 'Publication';
    const STATUS_DELETED       = 'Deleted';
    const STATUS_ACTIVE        = 'Active';

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
                ->setName('job:demo:xhamster');
    }

    /**
     * Initializes the job
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $app =$this->getHelper('silex')->getApplication();
        $this->httpSession = new HttpClient([
            'base_url' => 'https://xhamster.com/',
            'cookies' => [
                'prid'  => '--',
                'prs'   => '--',
                'stats' => 76,
            ],
            'defaults' => [
                'debug' => $output->isVerbose(),
            ],
        ]);

        $this->em = $this->getHelper('em')->getEntityManager();
        $this->logger = $app['monolog.factory'](get_called_class());
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outbound = $this->em->find('cx:videooutbound', $input->getArgument('videooutbound'));
        $outboundId = $input->getArgument('videooutbound');
        if (!$outbound) {
            $msg = sprintf("No videoOutbound for Id: {%s}", $outboundId);
            $this->logger->error($msg);
            throw new InvalidArgumentException($msg);
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

        $this->setCookie('.xhamster.com', 'UID',      $tubeuser->getParam('UID'));
        $this->setCookie('.xhamster.com', 'PWD',      $tubeuser->getParam('PWD'));
        $this->setCookie('.xhamster.com', 'USERNAME', $tubeuser->getParam('USERNAME'));

        if (!$this->isLoggedIn($tubeuser)) {
            $this->login($tubeuser);

            if (!$this->isLoggedIn($tubeuser)) {
                $msg = sprintf("Login succeeded but still no access. OutboundId: %s", $outboundId);
                $this->logger->error($msg);
                throw new InternalInconsistencyException($msg);
            }
        }

        $this->setCookie(
            '.xhamster.com',
            'x_' . $this->getCookie('USERNAME') . '_logined',
            '1'
        );

        // $sites = $this->getCppSites(); var_dump($sites); exit;

        if ($outbound->getExternalId()) {

            $output->write('<info>VideoOutbound has externalId set, only refreshing status ... </info>');
            $this->refreshVideoStatus($outbound);
            $output->writeln('<comment>' . $outbound->getStatus() . '</comment>');

        } else {

            $output->writeln('<info>Starting <comment>xHamster</comment> outbound upload...</info>');

            $output->writeln('<info> + selecting server</info>');
            $uploadBaseUrl = $this->getUploadBaseUrl();

            $output->writeln('<info> + validating video</info>');
            $this->validateVideo($uploadBaseUrl, $outbound);

            $output->writeln('<info> + preparing upload</info>');
            $this->prepareVideo($uploadBaseUrl, $outbound);

            $output->writeln('<info> + uploading video</info>');
            $this->uploadVideo($uploadBaseUrl, $outbound);

            $output->writeln('<info> + submitting</info>');
            $this->submitVideo($uploadBaseUrl, $outbound);

            $output->write('<info> + detecting external id ... </info>');
            $this->detectExternalId($outbound);
            $output->writeln('<comment>' . $outbound->getExternalId() . '</comment>');
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
        $response = $this->httpSession->get('/edit_profile.php');

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $body = (string) $response->getBody();

        // verify

        if (strpos($body, '<title>Edit Profile</title>') === false) {
            throw new UnexpectedResponseException('Failed to fetch profile details page');
        }

        if (strpos($body, '://upload.xhamster.com/producer_sites.php') === false) {
            throw new AccountStateException('xHamster user is not a content partner account');
        }

        if (stripos($body, '://xhamster.com/user/' . $tubeuser->getUsername()) === false) {
            throw new AccountMismatchException('xHamster response does not contain expected username');
        }

        $username = $this->getCookie('USERNAME');

        if (strtolower($username) != strtolower($tubeuser->getUsername())) {
            throw new AccountMismatchException('xHamster username cookie does not match our username');
        }

        $uid = $this->getCookie('UID');

        if ($tubeuser->getExternalId() && $uid != $tubeuser->getExternalId()) {
            throw new AccountMismatchException('xHamster UID cookie does not match our external ID');
        }

        $pwd = $this->getCookie('PWD');

        // persist

        $this->em->transactional(function ($em) use ($tubeuser, $username, $uid, $pwd) {
            $tubeuser->setParam('USERNAME', $username);
            $tubeuser->setParam('UID', $uid);
            $tubeuser->setParam('PWD', $pwd);
        });

        return true;
    }

    /**
     * Log into the tubesite
     *
     * @return void
     */
    protected function login(TubesiteUser $tubeuser)
    {
        // calculate "security hash"; lifted from their javascript

        $this->logger->info('Login as {tubeuser}', ['tubeuser' => $tubeuser->getUsername()]);
        $hash = function ($time) {
            $res1 = base_convert(bcsub($time, 24563844), 10, 16);
            $res2 = substr(base_convert($time, 10, 16), 3);
            return $res1 . ':' . $res2;
        };

        $time_submit = bcmul(microtime(true), 1000);
        $time_load   = bcsub($time_submit, 25 * 1000);

        $xsid        = $hash($time_load);
        $stats       = $hash($time_submit);

        $this
            ->httpSession
            ->getCookieJar()
            ->setCookie(new SetCookie([
                'Domain'  => 'xhamster.com',
                'Name'    => 'xsid',
                'Value'   => $xsid,
                'Discard' => true
            ]));

        // request

        $response = $this->httpSession->jsonGet('/ajax/login.php', [
            'headers' => [
                'Referer' => 'https://xhamster.com/login.php',
                'Accept'  => 'text/javascript, application/javascript, application/ecmascript, application/x-ecmascript, */*; q=0.01',
            ],
            'query' => [
                'act'      => 'login',
                'ref'      => 'https://upload.xhamster.com/producer.php',
                'stats'    => $stats,
                'act'      => 'login',
                'username' => $tubeuser->getUsername(),
                'password' => $tubeuser->getPassword(),
                'remember' => 'on',
                '_'        => $time_submit,
            ],
        ]);

        $body = (string) $response->getBody();

        // success

        if (strpos($body, 'window.location=') !== false) {
            // XXX: extract new xsid cookie?
            return;
        }

        // error

        $count = preg_match_all(
            "/login\.error\('(?P<field>#?\w+)',(?|'(?P<error>(?:[^'\\\\]|\\\\.)*)'|(?P<error>false)),false\);/",
            $body,
            $matches
        );

        if (!$count) {
            $msg = 'Login failed: unknown error; could not extract error from response body';
            $this->logger->error($msg, ['tubeuser' => $tubeuser->getUsername()]);
            throw new UnexpectedResponseException($msg . ':' . $body);
        }

        $errors = array_combine($matches['field'], $matches['error']);
        $errors = array_map(function ($d) {
            return $d === 'false' ? false : $d;
        }, $errors);

        if ($errors['#loginCaptcha']) {
            throw new AccountLockedException(sprintf(
                'xHamster login failed: xHamster account is blocked with captcha. '
                . 'Log in directly on the tubesite to unblock and then '
                . 'try again.'
            ));
        } elseif ($errors['username']) {
            throw new LoginException(sprintf(
                'xHamster login failed: invalid username; (%s)',
                $errors['username']
            ));
        } elseif ($errors['password']) {
            throw new LoginException(sprintf(
                'xHamster login failed: invalid password; (%s)',
                $errors['password']
            ));
        }

        throw new UnexpectedResponseException(
            sprintf('xHamster login failed: unknown error; (%s)', $body)
        );
    }

    /**
     * Log out from the tubesite
     *
     * @return void
     */
    protected function logout(TubesiteUser $tubeuser)
    {
    }

    /**
     * Get a list of Content-Partner-Program site details for the logged in
     * tubeuser
     *
     * @return array
     */
    protected function getCppSites()
    {
        $response = $this->httpSession->get('https://upload.xhamster.com/producer_sites.php', [
            'config' => [
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                ],
            ],
        ]);

        $dom = new DomCrawler();
        $dom->addHtmlContent((string) $response->getBody());

        // error

        $error = false;

        try {
            $error = $dom->filter('.main .error')->text();
        } catch (InvalidArgumentException $e) {
        }

        if ($error) {
            throw new UnexpectedResponseException('Could not get CPP sites: ' . $error);
        }

        // success

        $sites = $dom->filter('.boxC.sites .item')->each(function ($node) {
            $site = [
                'title'       => $node->filter('table tr.head td:nth-child(1)')->text(),
                'description' => $node->filter('table tr.head td:nth-child(2)')->text(),
            ];

            $href = $node
                ->filter('table tr:nth-child(3) td a:first-child')
                ->attr('href');

            list($ignore, $id) = explode('&id=', $href);

            $site['id'] = (int) $id;

            return $site;
        });

        return $sites;
    }

    /**
     * Get the base url for the video upload process from xHamster's
     * loadbalancer
     *
     * Note: upload servers do not support SSL
     *
     * @return Url
     */
    protected function getUploadBaseUrl()
    {
        $response = $this->httpSession->get('https://upload.xhamster.com/producer.php', [
            'config' => ['curl' => [CURLOPT_SSL_VERIFYPEER => false]],
        ]);

        if ($response->getStatusCode() != 302) {
            throw new UnexpectedResponseException('Failed to get redirect for loadbalanced upload host');
        }

        $redirectUrl = Url::fromString($response->getHeader('Location'));

        return new Url('http', $redirectUrl->getHost());
    }

    /**
     * Create Video: Validate
     */
    protected function validateVideo(Url $baseUrl, VideoOutbound $outbound)
    {
        $this->em->transactional(function ($em) use ($outbound) {
            //$outbound->setStatus(VideoOutbound::STATUS_WORKING);
        });

        $video    = $outbound->getVideo();
        $tubeuser = $outbound->getTubesiteUser();

        $response = $this->httpSession->jsonPost($baseUrl->combine('/photos/ajax.php?ajax=1&act=sponsor&id=2&tpl2'), [
            'headers' => [
                'Referer' => (string) $baseUrl->combine('/producer.php'),
                    'Origin'  => (string) $baseUrl,
                ],
                'body' => $this->getUploadData($outbound),
            ]);

        $body = (string) $response->getBody();

        // success

        if ($body == 'spUpload.upload();UberUpload.linkUpload();') {
            return;
        }

        // error

        $count = preg_match_all(
            "/spUpload\.spError\('(?P<field>#?\w+)','(?P<error>(?:[^'\\\\]|\\\\.)*)'\);/",
            $body,
            $matches
        );

        if (!$count) {
            throw new UnexpectedResponseException(
                'xHamster metadata validation failed: '
                . 'unknown error; could not extract error from response body: '
                . $body
            );
        }

        $errors = array_combine($matches['field'], $matches['error']);

        throw new UploadException(
            'xHamster metadata validation failed: '
            . implode(', ', $errors)
            . ' (' . json_encode($errors) . ')'
        );
    }

    /**
     * Create Video: Prepare
     */
    protected function prepareVideo(Url $baseUrl, VideoOutbound $outbound)
    {
        $response = $this->httpSession->jsonGet($baseUrl->combine('/photos/uploader2/sp.prepare.2.php'), [
            'headers' => [
                'Referer' => (string) $baseUrl->combine('/producer.php'),
                    'Origin'  => (string) $baseUrl,
                ],
                'query' => [
                    '_' => bcmul(microtime(true), 1000),
                    ],
                ]);

        $body = (string) $response->getBody();

        $count = preg_match(
            "/UberUpload\.startUpload\(\"(?P<upload_id>\w+)\",\d,\d\);/",
            $body,
            $matches
        );

        // error

        if (!$count) {
            throw new UnexpectedResponseException(
                'xHamster prepare failed: could not '
                . 'extract upload ID from response body: '
                . $body
            );
        }

        // success

        $this->em->transactional(function ($em) use ($outbound, $matches) {
            $outbound->setParam('upload_id', $matches['upload_id']);
        });
    }

    /**
     * Create Video: Upload
     */
    protected function uploadVideo(Url $baseUrl, VideoOutbound $outbound)
    {
        $uploadId = $outbound->getParam('upload_id');

        // s3 object  TODO: refactor

        $app = $this->getHelper('silex')->getApplication();
        $s3  = $app['aws']->get('s3');;

        $video = $outbound->getVideo();
        $videoFile = $outbound->getVideoFile();

        $objectUrl = $s3->getObjectUrl(
            $app['config']['aws']['bucket'],
            $videoFile->getStoragePath(),
            '+1 hour'
        );

        $stream = fopen($objectUrl, 'r', false);

        // upload

        $request = $this->httpSession->createRequest(
            'POST',
            $baseUrl->combine('/cgi-bin/ubr_upload.6.8.pl'),
            [
                'headers' => [
                    'Referer' => (string) $baseUrl->combine('/producer.php'),
                    'Connection' => 'keep-alive',
                ],
                'query' => [
                    'upload_id' => $uploadId,
                ],
                'body' => $this->getUploadData($outbound),
            ]
        );

        $request->getBody()
            ->replaceFields($this->getUploadData($outbound))
            ->removeField('slot1_file')
            ->addFile(new PostFile(
                'slot1_file',
                $stream,
                $videoFile->getFilename(),
                ['Content-Type' => $videoFile->getFiletype()]
            ));

        $response = $this->httpSession->send($request);

        $body = (string) $response->getBody();

        // error

        if (strpos($body, 'UberUpload.redirectAfterUpload') === false) {
            throw new UnexpectedResponseException('xHamster upload failed: ' . $body);
        }

        if (strpos($body, $uploadId) === false) {
            throw new UnexpectedResponseException(sprintf(
                'xHamster upload failed: Response does not include expected '
                . 'id `%s`: %s',
                $uploadId,
                $body
            ));
        }

        // success

        //var_dump($response); print((string) $response->getBody());
    }

    /**
     * Create Video: Submit for Processing
     */
    protected function submitVideo(Url $baseUrl, VideoOutbound $outbound)
    {
        $response = $this->httpSession->get($baseUrl->combine('/producer.php'), [
            'headers' => [
                'Referer' => (string) $baseUrl->combine('/producer.php'),
            ],
            'query' => [
                'upload_id' => $outbound->getParam('upload_id'),
            ],
        ]);

        $body = (string) $response->getBody();

        // success

        if (strpos($body, 'Your video was successfully uploaded.') !== false) {
            return;
        }

        // error

        $dom = new DomCrawler();
        $dom->addHtmlContent($body);

        try {
            $error = $dom->filter('.main .error')->text();
        } catch (InvalidArgumentException $e) {
            $error = 'unknown error; could not extract error from response body';
        }

        throw new UnexpectedResponseException('xHamster submit failed: ' . $error);
    }

    /**
     * Detect the external ID for the video we just submitted
     *
     * Assume that immediately after the submit call, our video is on the top
     * of the list. Validate that this first video matches expected status,
     * title and site. If all looks good, take the `vid` and store it.
     *
     * Unfortunately, xHamster does not return their video id anywhere during
     * the upload process, so we have to extract it like this.
     *
     * @return void
     */
    protected function detectExternalId(VideoOutbound $outbound)
    {
        $app      = $this->getHelper('silex')->getApplication();

        $video    = $outbound->getVideo();
        $tubeuser = $outbound->getTubesiteUser();

        $expectedSite  = $tubeuser->getParam('site')['title'];
        $expectedTitle = substr(str_replace($this->forbiddenStrings, ' ', ($app['debug'] ? 'TEST - ' : '') . $video->getTitle()), 0, 60); // FIXME: refactor

        $list = $this->getVideosList(1);
        $match = null;

        foreach ($list as $item) {
            if ($item['status'] == self::STATUS_NOT_CONVERTED
                && $item['added'] == '1 minute ago'
                && $item['site'] == $expectedSite
                && $item['title'] == $expectedTitle
            ) {
                $match = $item;
                break;
            }
        }

        // error

        if (!$match) {
            $msg = sprintf(
                'Could not find uploaded file on the status page to '
                    . 'detect external id: video outbound id: %s',
                $outbound->getExternalId()
            );

            $this->logger->error($msg);
            throw new InternalInconsistencyException($msg);
        }

        // success

        $this->em->transactional(function ($em) use ($outbound, $match) {
            $outbound->setExternalId($match['id']);
        });
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
        $externalId = $outbound->getExternalId();

        // find video on paginated html lists

        $match = null;

        for (
            $i = 1; $list = $this->getVideosList($i), count($list) > 1; $i++
        ) {
            if (isset($list[$externalId])) {
                // found the video
                $match = $list[$externalId];
                break;
            }

            if (max(array_keys($list)) < $externalId) {
                // we paginated past the id we search for
                break;
            }
        }

        // error

        if (!$match) {
            throw new InternalInconsistencyException(sprintf(
                'Could not find `%d` on the video status pages',
                $externalId
            ));
        }

        // success

        $this->em->transactional(function ($em) use ($outbound, $match) {
            $params = array_intersect_key($match, array_flip([
                'status',
            ]));

            $outbound->addParams($params);

            switch($match['status']) {
                case self::STATUS_NOT_CONVERTED:
                    $outbound->setStatus(VideoOutbound::STATUS_WORKING);
                    break;

                case self::STATUS_PUBLICATION:
                case self::STATUS_ACTIVE:
                    $outbound->setStatus(VideoOutbound::STATUS_COMPLETE);
                    break;

                case self::STATUS_REPOST:
                case self::STATUS_DELETED:
                    $outbound->setStatus(VideoOutbound::STATUS_ERROR);
                    break;

                default:
                    throw new UnexpectedValueException(sprintf(
                        'Unexpected status `%s` for outbound `%d`',
                        $match['status'], $outbound->getId()
                    ));
                    break;
            }
        });
    }

    protected function getVideosList($page = 1)
    {
        $response = $this->httpSession->get(
            ['/my_vids/all/{page}.html', ['page' => $page]]
        );

        $dom = new DomCrawler();
        $dom->addHtmlContent((string) $response->getBody());

        // error

        $error = false;

        try {
            $error = $dom->filter('.main .error')->text();
        } catch (InvalidArgumentException $e) {
        }

        if ($error) {
            throw new UnexpectedResponseException('Could not get video list: ' . $error);
        }

        // success

        $videos = $dom->filter('.boxC.myVidList .myVideo')->each(function ($node) {
            $video = [
                'id' => (int) $node->attr('vid'),
                ];

            $info = $node->filter('.info')->html();
            $info = explode('<br>', $info);
            $info = array_map('trim', $info);

            foreach ($info as $line) {
                if (strpos($line, 'class="title"') !== false) {
                    $video['title'] = strip_tags($line);
                } elseif (preg_match('/^<span>(?P<key>[^:]+):<\/span>(?P<value>.+)$/', $line, $matches)) {
                    $video[strtolower($matches['key'])] = trim(strip_tags($matches['value']));
                } else {
                    // FIXME: log warning: could not extract field
                }
            }

            try {
                $statusImage = $node->filter('.thumb2 img')->attr('src');
                $video['status_image'] = $statusImage;
            } catch (InvalidArgumentException $e) {
            }

            return $video;
        });

        $videos = array_column($videos, null, 'id');

        return $videos;
    }

    protected function getUploadData(VideoOutbound $outbound)
    {
        $app      = $this->getHelper('silex')->getApplication();

        $video    = $outbound->getVideo();
        $tubeuser = $outbound->getTubesiteUser();

        $videoFile = $outbound->getVideoFile();

        return [
            // TODO: refactor
            'slot1_title' =>
                substr(str_replace($this->forbiddenStrings, ' ', ($app['debug'] ? 'TEST - ' : '') . $video->getTitle()), 0, 60),
            'slot1_descr' =>
                substr(str_replace($this->forbiddenStrings, ' ', ($app['debug'] ? 'TEST PLEASE REJECT - ' : '') . $video->getDescription()), 0, 500),

            'slot1_site' => $tubeuser->getParam('site')['id'],

            // TODO: pull from video tags
            'slot1_chanell'    => '.101',
            'slot1_channels101' => '',

            'slot1_fileType'  => '',
            'slot1_http_url'  => '',
            'slot1_http_user' => '',
            'slot1_http_pass' => '',
            'slot1_ftp_url'   => '',
            'slot1_ftp_user'  => '',
            'slot1_ftp_pass'  => '',
            'slot1_file'      => $videoFile->getFilename(),
        ];
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

