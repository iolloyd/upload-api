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

namespace Cloud\Resque\Plugin\History;

use DateTime;
use SplObjectStorage;
use Cloud\Resque\Job;
use Cloud\Resque\Job\Failure;
use Cloud\Resque\Resque;
use Cloud\Resque\Event\JobEvents;
use Cloud\Resque\Event\ResqueEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Resque plugin to keep track of the processing history and display it in
 * `resque-web`
 *
 * @see https://github.com/ilyakatz/resque-history/
 */
class HistoryPlugin implements EventSubscriberInterface
{
    /**
     * @var string  key of the history list
     */
    const QUEUE = 'resque_history';

    /**
     * @var int  maximum number of records to keep in the list
     */
    const MAX_RECORDS = 3000;

    /**
     * @var Resque
     */
    protected $resque;

    /**
     * @var SplObjectStorage  a hash of job start times used for timing
     */
    protected $startTimes;

    /**
     * Constructor
     */
    public function __construct(Resque $resque, array $options = [])
    {
        $this->resque = $resque;
        $this->startTimes = new SplObjectStorage();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JobEvents::BEFORE_PERFORM => 'beforePerform',
            JobEvents::AFTER_PERFORM  => 'afterPerform',
            JobEvents::ON_FAILURE     => 'onFailure',
        ];
    }

    /**
     * beforePerform: Remember the start time of the job
     */
    public function beforePerform(ResqueEvent $event)
    {
        $this->startTimes->attach($event['job'], new DateTime());
    }

    /**
     * afterPerform: Save the history and timing
     */
    public function afterPerform(ResqueEvent $event)
    {
        $this->pushHistory($event['job']);
    }

    /**
     * onFailure: Save the history, error details, and timing
     */
    public function onFailure(ResqueEvent $event)
    {
        $this->pushHistory($event['job'], $event['failure']);
    }

    /**
     * Create a job history record in Redis for the given job and optional
     * exception in case of failure
     *
     * @param  Job     $job
     * @param  Failure $failure
     */
    protected function pushHistory(Job $job, Failure $failure = null)
    {
        $now = new DateTime();

        if (isset($this->startTimes[$job])) {
            $started = $this->startTimes[$job];
            $this->startTimes->detach($job);
            $duration = $now->getTimestamp() - $started->getTimestamp();
        } else {
            $duration = -1;
        }

        $payload = $this->resque->normalize($job);

        $data = [
            'class'     => $payload['class'],
            'time'      => $now->format('Y-m-d H:i'),
            'args'      => $payload['args'],
            'execution' => $duration,
        ];

        if ($failure) {
            $data['error'] = $failure->getError();
        }

        $this->resque->redis()->lpush(
            $this->resque->prefix(self::QUEUE),
            $this->resque->encode($data)
        );

        $this->resque->redis()->ltrim(
            $this->resque->prefix(self::QUEUE),
            0,
            static::MAX_RECORDS - 1
        );
    }
}
