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
        $output->writeln('');
        $output->writeln('<info>Queueing refresh</info> ... in 10 seconds');
        sleep(10);
        \Resque::enqueue('default', get_called_class(), ['videooutbound' => $outbound->getId()]);
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

        $response = $this->httpSession->jsonPost('/upload/create-videos/', [
            'body' => [
                'file' => $outbound->getFilename(), // just the filename here
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
        // s3 object  TODO: refactor

        $app   = $this->getHelper('slim')->getSlim();
        $s3    = $app->s3;

        $video = $outbound->getVideo();
        $key   = sprintf('videos/%d/raw/%s', $video->getId(), $video->getFilename());

        $object = $s3->getObject([
            'Bucket' => $app->config('s3.bucket'),
            'Key'    => $key,
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
                $outbound->getFilename(),
                ['Content-Type' => $outbound->getFiletype()]
            ));

        $response = $this->httpSession->send($request);

        $data = $response->json()[0];

        // verify

        if ($data['size'] != $outbound->getFilesize()) {
            throw new InternalInconsistencyException('YouPorn `size` does not match our filesize');
        }
    }

    /**
     * Post metadata and submit remote video for review
     */
    protected function submitVideo(VideoOutbound $outbound)
    {
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
                        substr('(TEST ONLY) ' . str_replace($this->forbiddenStrings, ' ', $video->getTitle()), 0, 250),

                    'videoedit[description]' =>
                        substr('(TEST ONLY - PLEASE REJECT) ' . str_replace($this->forbiddenStrings, ' ', $video->getDescription()), 0, 2000),

                    // TODO: pull from video tags
                    'videoedit[uploader_category_id]' => '19',
                    'videoedit[orientation]' => 'straight',
                    'videoedit[tags]' => '',
                    'videoedit[pornstars]' => '',

                    //'videoedit[content_partner_site_id]' =>
                        //(string) $tubeuser->getParam('content_partner_site_id'),

                    'videoedit[content_partner_site_id]' => '2242',
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

