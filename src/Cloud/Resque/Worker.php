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

namespace Cloud\Resque;

use DateTime;
use DateTimeZone;
use Exception;
use Cloud\Resque\Event\ResqueEvent;
use Cloud\Resque\Event\WorkerEvents;
use Cloud\Resque\Exception\DirtyExitException;
use Psr\Log\LoggerInterface;
use Spork\Fork;
use Spork\ProcessManager;
use Spork\EventDispatcher\WrappedEventDispatcher;
use Spork\Exception\ProcessControlException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A Resque Worker processes jobs. On platforms that support fork(2),
 * the worker will fork off a child to process each job. This ensures
 * a clean slate when beginning the next job and cuts down on gradual
 * memory growth as well as low level failures.
 */
class Worker
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $hostname;

    /**
     * @var integer
     */
    protected $pid;

    /**
     * @var array
     */
    protected $queues;

    /**
     * @var Resque
     */
    protected $resque;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * @var Storage\Workers
     */
    protected $storage;

    /**
     * @var ProcessManager
     */
    protected $spork;

    /**
     * @var bool
     */
    protected $isPaused = false;

    /**
     * @var bool
     */
    protected $isShuttingDown = false;

    /**
     * @var bool
     */
    protected $isKilling = false;

    /**
     * Constructor
     *
     * @param Resque $resque  resque instance that manages this worker
     * @param array  $queues  list of queue names this worker will listen to
     */
    public function __construct(Resque $resque, array $queues)
    {
        $this->queues = $queues;

        $this->resque  = $resque;
        $this->logger  = $resque->getLogger();
        $this->events  = $resque->getEventDispatcher();
        $this->storage = $resque->getWorkersStorage();

        $this->pid = getmypid();
        $this->hostname = gethostname();
        $this->id = sprintf(
            '%s:%d:%s',
            $this->hostname,
            $this->pid,
            implode(',', $this->queues)
        );

        $this->spork = new ProcessManager(new WrappedEventDispatcher($this->events));
        $this->spork->zombieOkay(true); // we handle terminating the forks ourselves
    }

    /**
     * This is the main workhorse method. Called on a Worker instance,
     * it begins the worker life cycle.
     *
     * The following events occur during a worker's life cycle:
     *
     * 1. Startup:   Signals are registered, dead workers are pruned,
     *               and this worker is registered.
     * 2. Work loop: Jobs are pulled from a queue and processed.
     * 3. Teardown:  This worker is unregistered.
     *
     * @param  float $interval  the polling frequency. default is 5 seconds,
     *                            but for a semi-active site you may want to
     *                            use a smaller value
     *
     * @return void
     */
    public function work($interval = Resque::DEFAULT_INTERVAL)
    {
        $this->logger->debug(__METHOD__);

        declare(ticks = 1);

        $this->procline('Starting');

        $this->registerSignalHandlers();
        $this->pruneDeadWorkers();

        $this->events->dispatch(WorkerEvents::BEFORE_FIRST_FORK, new ResqueEvent($this->resque, [ 'worker' => $this ]));

        $this->registerWorker();

        while (true) {
            if ($this->isShuttingDown()) {
                break;
            }

            if (!$this->isPaused()) {
                $this->procline('Waiting for ' . implode(',', $this->queues));
                $this->logger->debug('Polling for {interval} seconds...', [ 'interval' => $interval ]);

                // poll

                $job = $this->reserve($interval);

                if ($this->isShuttingDown()) {
                    // signal received while block
                    // TODO: requeue job or fail it
                }

                if (!$job) {
                    continue;
                }

                // work

                $this->logger->info('Got job: {job}', [ 'job' => $job, 'worker' => $this ]);

                $job->setWorker($this);
                $this->workingOn($job);

                $this->procline('Processing ' . $job->getQueue() . ' since ' . time()
                    . ' [' . $job->getClass() . ']');

                // fork

                $this->events->dispatch(
                    WorkerEvents::BEFORE_FORK,
                    new ResqueEvent($this->resque, [ 'worker' => $this, 'job' => $job ])
                );

                $fork = $this->spork->fork(function () use ($job) {
                    $this->unregisterSignalHandlers();
                    $this->resque->ping();

                    $this->events->dispatch(
                        WorkerEvents::AFTER_FORK,
                        new ResqueEvent($this->resque, [ 'worker' => $this, 'job' => $job ])
                    );

                    $job->perform();
                });

                $this->procline('Forked ' . $fork->getPid() . ' at ' . time(), false);
                $fork->wait(true);
                $this->logger->info('Fork exited with code {exitCode}', ['exitCode' => $fork->getExitStatus()]);

                $this->resque->ping();

                if (!$fork->isSuccessful()) {
                    $job->fail(new DirtyExitException(sprintf(
                        'Forked process crashed with exit code %d',
                        $fork->getExitStatus()
                    )));
                }

                // done

                $this->doneWorking();
            } else {
                $this->procline('Paused');
                $this->logger->debug('Sleeping for {interval} seconds', [ 'interval' => $interval ]);
                usleep($interval * 1000000);
            }
        }

        $this->unregisterWorker();
    }

    /**
     * Check if this worker is currently processing a job
     *
     * @return bool
     */
    public function isWorking()
    {
        return $this->storage->hasWorkingOn($this->id);
    }

    /**
     * Get the identifier key for this worker
     *
     *   <hostname>:<pid>:<queue>[,<queue>[,...]]
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getId();
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Register this worker and set the starting time
     */
    protected function registerWorker()
    {
        $this->logger->debug(__METHOD__);

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->storage->addWorker($this->id, $now->format(DateTime::ISO8601));
    }

    /**
     * Unregister this worker
     */
    protected function unregisterWorker()
    {
        $this->logger->debug(__METHOD__);

        // TODO: log current job as failed, if still running
        $this->storage->removeWorker($this->id);
    }

    /**
     * Register the job this worker is currently processing
     *
     * @param Job $job
     */
    protected function workingOn(Job $job)
    {
        $this->logger->debug(__METHOD__);

        $now = new DateTime('now', new DateTimeZone('UTC'));

        $data = [
            'queue'   => $job->getQueue(),
            'run_at'  => $now->format(DateTime::ISO8601),
            'payload' => $this->resque->normalize($job),
        ];

        $this->storage->setWorkingOn($this->id, $data);
    }

    /**
     * Clears the "working on" state of this worker
     */
    protected function doneWorking()
    {
        $this->logger->debug(__METHOD__);
        $this->storage->unsetWorkingOn($this->id);
    }

    /**
     * Looks for any workers which should be running on this server
     * and, if they're not, removes them from Redis.
     *
     * This is a form of garbage collection. If a server is killed by a
     * hard shutdown, power failure, or something else beyond our
     * control, the Resque workers will not die gracefully and therefore
     * will leave stale state information in Redis.
     *
     * By checking the current Redis state against the actual
     * environment, we can determine if Redis is old and clean it up a bit.
     */
    protected function pruneDeadWorkers()
    {
        $this->logger->debug(__METHOD__);

        $allWorkers = $this->storage->all();

        if (!count($allWorkers)) {
            return;
        }

        $localPids = $this->workerPids();

        foreach ($allWorkers as $worker) {
            list($hostname, $pid, $queues) = explode(':', $worker);
            $queues = explode(',', $queues);

            if (!in_array('*', $queues)
                && !count(array_intersect($this->queues, $queues))
            ) {
                /*
                 * if the worker we are trying to prune does not belong to the
                 * queues we are listening to, we should not touch it. Attempt
                 * to prune a worker from different queues may easily result in
                 * an unknown class exception, since that worker could easily
                 * be even written in different language.
                 */
                continue;
            }

            if ($this->hostname != $hostname) {
                continue;
            }

            if (!in_array($pid, $localPids)) {
                $this->logger->warning('Pruning dead worker: {worker}', ['worker' => $worker]);
                $this->storage->removeWorker($worker);
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Register the signal handlers
     */
    protected function registerSignalHandlers()
    {
        $this->logger->debug(__METHOD__);

        pcntl_signal(SIGTERM, [$this, 'kill']);
        pcntl_signal(SIGINT,  [$this, 'kill']);

        pcntl_signal(SIGQUIT, [$this, 'shutdown']);

        pcntl_signal(SIGUSR1, [$this, 'killJobs']);

        pcntl_signal(SIGUSR2, [$this, 'pause']);
        pcntl_signal(SIGCONT, [$this, 'unpause']);
    }

    /**
     * Unregister the signal handlers
     */
    protected function unregisterSignalHandlers()
    {
        $this->logger->debug(__METHOD__);

        pcntl_signal(SIGTERM, function () {
            // ignore subsequent terms
            pcntl_signal(SIGTERM, SIG_IGN);
            throw new Exception('Terminated');
        });
        pcntl_signal(SIGINT,  SIG_DFL);
        pcntl_signal(SIGQUIT, SIG_DFL);
        pcntl_signal(SIGUSR1, SIG_DFL);
        pcntl_signal(SIGUSR2, SIG_DFL);
    }

    /**
     * Sets the procline and logs it as a message
     *
     * Procline is always in the format of:
     *   phpresquecx-<version>: <title>
     *
     * PHP 5.5+ or the proctitle PECL library is required.
     *
     * @param string $title
     * @param bool   $quiet  if the title should be logged as info
     */
    protected function procline($title, $quiet = false)
    {
        $procline = sprintf('phpresquecx-%s: %s', Resque::VERSION, $title);

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($procline);
        } elseif (function_exists('setproctitle')) {
            setproctitle($procline);
        }

        if (!$quiet) {
            $this->logger->info($title);
        } else {
            $this->logger->debug($title);
        }
    }

    /**
     * Find the pids of all the other workers on this machine.
     *
     * Useful when pruning dead workers on startup.
     *
     * @return array
     */
    protected function workerPids()
    {
        $cmd = sprintf(
            'ps -A -o pid,command | grep %s | grep -v "resque-web"',
            escapeshellarg(sprintf('phpresquecx-%s: ', Resque::VERSION))
        );

        exec($cmd, $output);

        return array_map(function ($d) {
            return explode(' ', trim($d), 2)[0];
        }, $output);
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Immediately kill jobs then exit
     *
     * TODO: follow logic in https://hone.heroku.com/resque/2012/08/21/resque-signals.html
     */
    public function kill()
    {
        $this->logger->debug(__METHOD__);

        if ($this->isKilling) { return; }

        $this->logger->notice('Forcing shutdown...', ['worker' => $this]);

        $this->isKilling = true;
        $this->isShuttingDown = true;
        $this->killJobs();
    }

    /**
     * Wait for jobs to finish processing then exit
     */
    public function shutdown()
    {
        $this->logger->debug(__METHOD__);

        if ($this->isShuttingDown) { return; }

        $this->logger->notice('Shutting down...', ['worker' => $this]);
        $this->isShuttingDown = true;
    }

    /**
     * Check if this worker is currently shutting down
     *
     * @return bool
     */
    public function isShuttingDown()
    {
        return $this->isShuttingDown;
    }

    /**
     * Pause worker, no new jobs will be processed
     */
    public function pause()
    {
        $this->logger->debug(__METHOD__);
        $this->logger->notice('Pausing job processing...', ['worker' => $this]);
        $this->events->dispatch(WorkerEvents::BEFORE_PAUSE, new ResqueEvent($this->resque, [ 'worker' => $this ]));
        $this->isPaused = true;
    }

    /**
     * Resume worker
     */
    public function unpause()
    {
        $this->logger->debug(__METHOD__);
        $this->logger->notice('Resuming job processing...', ['worker' => $this]);
        $this->events->dispatch(WorkerEvents::AFTER_PAUSE, new ResqueEvent($this->resque, [ 'worker' => $this ]));
        $this->isPaused = false;
    }

    /**
     * Check if this worker has paused processing jobs
     *
     * @return bool
     */
    public function isPaused()
    {
        return $this->isPaused;
    }

    /**
     * Immediately kill all running child jobs
     *
     * TODO: follow logic in https://hone.heroku.com/resque/2012/08/21/resque-signals.html
     */
    public function killJobs()
    {
        $this->logger->debug(__METHOD__);
        $this->logger->notice('Stopping all running child jobs...', ['worker' => $this]);

        try {
            $this->logger->debug('Sending TERM signal to forks');
            $this->spork->killAll(SIGTERM);
            $this->spork->killAll(SIGKILL);
        } catch (ProcessControlException $e) {
        }

        // TODO: finish
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Pop a job off one of the queues of this worker
     *
     * @param integer $blockingTimeout  maximum number of seconds to wait for
     *                                    something to be pushed on the queue
     *
     * @return Job|null  job instance or null if the queue was empty
     */
    protected function reserve($blockingTimeout)
    {
        $this->logger->debug(__METHOD__);

        $data = $this->resque->getJobsStorage()->blpop($this->queues, $blockingTimeout);

        if (!$data) {
            return null;
        }

        $job = $this->resque->denormalize(
            $data['item'],
            'Cloud\Resque\Job',
            ['queue' => $data['queue']]
        );

        return $job;
    }
}
