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

namespace Cloud\Resque\Plugin\Status;

use Redis;
use SplObjectStorage;
use Cloud\Resque\Job;
use Cloud\Resque\Resque;
use Cloud\Resque\Event\JobEvents;
use Cloud\Resque\Event\ResqueEvent;
use Cloud\Resque\Exception\DontPerformException;
use Cloud\Resque\Plugin\Status\Job\Status;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Track extended job status information
 *
 * Compatible with the Ruby modules `resque-status` and `resque-web`.
 *
 * @see https://github.com/quirkey/resque-status
 */
class StatusPlugin implements EventSubscriberInterface
{
    /**
     * @var string  seconds after which to expire status data
     */
    const EXPIRE_IN = 86400;

    /**
     * @var Resque
     */
    protected $resque;

    /**
     * Constructor
     */
    public function __construct(Resque $resque, array $options = [])
    {
        $this->resque = $resque;
        $this->storage = new StatusStorage($resque);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JobEvents::BEFORE_ENQUEUE     => 'beforeEnqueue',
            JobEvents::AFTER_NORMALIZE    => 'afterNormalize',
            JobEvents::BEFORE_DENORMALIZE => 'beforeDenormalize',
            JobEvents::BEFORE_PERFORM     => 'beforePerform',
            JobEvents::AFTER_PERFORM      => 'afterPerform',
            JobEvents::ON_FAILURE         => 'onFailure',
        ];
    }

    /**
     * beforeEnqueue: Create status and inject UUID into payload
     */
    public function beforeEnqueue(ResqueEvent $event)
    {
        $job  = $event['job'];
        $item = $event['item'];

        // set uuid

        $uuid = static::generateUuid();
        $job->setParameter('uuid', $uuid);

        // create status object

        $status = new Status($uuid);

        $status->setName($item['class']);
        $status->setStatus(Status::STATUS_QUEUED, 'Queued at ' . time());
        $status->setPayload($item);

        $this->save($status);

        // inject id into payload

        $item['args'] = [[
            'uuid'    => $uuid,
            'options' => $item['args'][0],
        ]];

        $event['item'] = $item;
    }

    /**
     * afterNormalize: Inject UUID into payload
     */
    public function afterNormalize(ResqueEvent $event)
    {
        $job  = $event['job'];
        $item = $event['item'];

        if (!$job->hasParameter('uuid')) {
            return;
        }

        $uuid = $job->getParameter('uuid');

        // inject id into payload

        $item['args'] = [[
            'uuid'    => $uuid,
            'options' => $item['args'][0],
        ]];

        $event['item'] = $item;
    }

    /**
     * beforeDenormalize: Restore original payload and extract UUID
     */
    public function beforeDenormalize(ResqueEvent $event)
    {
        $job  = $event['job'];
        $item = $event['item'];

        if (!isset($item['args'][0]['uuid'])) {
            return;
        }

        $uuid = $item['args'][0]['uuid'];

        // set uuid

        $job->setParameter('uuid', $uuid);

        // restore original payload

        $item['args']  = [$item['args'][0]['options']];
        $event['item'] = $item;
    }

    /**
     * beforePerform: Check for kill flag and update status
     */
    public function beforePerform(ResqueEvent $event)
    {
        $job = $event['job'];

        if (!$job->hasParameter('uuid')) {
            return;
        }

        $uuid = $job->getParameter('uuid');

        // check kill flag

        if ($this->storage->killFlagExists($uuid)) {
            $this->storage->killFlagUnset($uuid);
            throw new DontPerformException('Killed');
        }

        // update status

        $status = $this->load($uuid);
        $status->setStatus(Status::STATUS_WORKING);

        $this->save($status);
    }

    /**
     * afterPerform: Update status
     */
    public function afterPerform(ResqueEvent $event)
    {
        $job = $event['job'];

        if (!$job->hasParameter('uuid')) {
            return;
        }

        $uuid = $job->getParameter('uuid');

        // update status

        $status = $this->load($uuid);
        $status->setStatus(Status::STATUS_COMPLETED, 'Completed at ' . time());

        $this->save($status);
    }

    /**
     * onFailure: Update status
     */
    public function onFailure(ResqueEvent $event)
    {
        $job       = $event['job'];
        $failure   = $event['failure'];
        $exception = $event['exception'];

        if (!$job->hasParameter('uuid')) {
            return;
        }

        $uuid = $job->getParameter('uuid');

        // update status

        $status = $this->load($uuid);

        if ($exception instanceof DontPerformException) {
            $status->setStatus(Status::STATUS_KILLED, sprintf(
                'Cancelled: %s',
                $failure->getError()
            ));
        } else {
            $status->setStatus(Status::STATUS_FAILED, sprintf(
                'Failed: [%s] %s',
                $failure->getException(),
                $failure->getError()
            ));
        }

        $this->save($status);
    }

    /**
     * Get the status object for the given job
     *
     * @param Job $job
     *
     * @return Status
     */
    public function getStatus(Job $job)
    {
        if (!$job->hasParameter('uuid')) {
            throw new \Exception('Cannot get status of job without UUID');
        }

        $uuid = $job->getParameter('uuid');

        return $this->load($uuid);
    }

    /**
     * Save the given status in Redis
     *
     * @param Status $status
     *
     * @return StatusPlugin
     */
    public function save(Status $status)
    {
        $uuid = $status->getUuid();
        $item = $this->resque->normalize($status);

        $merged = array_replace($this->storage->get($uuid) ?: [], $item);

        $this->storage->set($uuid, $merged, static::EXPIRE_IN);

        return $this;
    }

    /**
     * Get the status with the given UUID
     *
     * @param string $uuid
     *
     * @return Status
     */
    public function load($uuid)
    {
        $item = $this->storage->get($uuid);

        if (!$item) {
            throw new \Exception('Could not load status');
        }

        $status = $this->resque->denormalize($item, 'Cloud\Resque\Plugin\Status\Job\Status');

        return $status;
    }

    /**
     * Generate a unique job ID
     *
     * @return string
     */
    public static function generateUuid()
    {
        return uniqid();
    }
}
