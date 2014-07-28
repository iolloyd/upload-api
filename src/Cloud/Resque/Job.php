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

use ArrayObject;
use Exception;
use JsonSerializable;
use Cloud\Resque\Event\JobEvents;
use Cloud\Resque\Event\ResqueEvent;
use Cloud\Resque\Job\Failure;
use Cloud\Resque\Job\PerformInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * A Resque Job represents a unit of work
 *
 * Each job lives on a single queue and has an associated payload object. The
 * payload is a hash with two attributes: `class` and `args`.
 *
 * The `class` is the name of the class which should be used to run the job.
 * The `args` are an array of arguments which should be passed to the class's
 * `perform` class-level method.
 */
class Job implements NormalizableInterface, DenormalizableInterface, JsonSerializable
{
    /**
     * @var string
     */
    protected $queue;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var mixed
     */
    protected $args;

    /**
     * @var Worker
     */
    protected $worker;

    /**
     * @var Resque
     */
    protected $resque;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * @var Storage\Jobs
     */
    protected $storage;

    /**
     * @var object  the payload class instance
     */
    protected $instance;

    /**
     * @var ArrayObject  runtime parameters from plugins
     */
    protected $parameters;

    /**
     * Constructor
     *
     * @param Resque $resque   Resque management instance
     */
    public function __construct(Resque $resque)
    {
        $this->resque  = $resque;
        $this->logger  = $resque->getLogger();
        $this->events  = $resque->getEventDispatcher();
        $this->storage = $resque->getJobsStorage();

        $this->parameters = new ArrayObject();
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Creates a job by placing it on a queue
     *
     * @return Job
     */
    public function create()
    {
        $this->logger->debug(__METHOD__);

        $event = new ResqueEvent($this->resque, [
            'job'   => $this,
            'queue' => $this->queue,
            'item'  => $this->resque->normalize($this),
        ]);

        $this->events->dispatch(JobEvents::BEFORE_ENQUEUE, $event);
        $this->storage->push($event['queue'], $event['item']);
        $this->events->dispatch(JobEvents::AFTER_ENQUEUE, $event);

        return $this;
    }

    /**
     * Removes a job from a queue
     *
     * @return Job
     */
    public function destroy()
    {
        $this->logger->debug(__METHOD__);

        $items = $this->storage->peek($this->queue, 0, -1);

        foreach ($items as $item) {
            // todo: fix id lookup with resque-status
            if (isset($item['id']) && $item['id'] === $this->id) {
                $event = new ResqueEvent($this->resque, [
                    'job'   => $this,
                    'queue' => $this->queue,
                    'item'  => $item,
                ]);

                $this->events->dispatch(JobEvents::BEFORE_DESTROY, $event);
                $this->storage->rem($event['queue'], $event['item'], 0);
                $this->events->dispatch(JobEvents::AFTER_DESTROY, $event);
            }
        }

        return $this;
    }

    /**
     * Executes a job
     *
     * Calls `perform()` on the class given in the payload with the
     * arguments given in the payload.
     *
     * @return Job
     */
    public function perform()
    {
        $this->logger->debug(__METHOD__);

        $success = false;

        set_exception_handler([$this, 'fail']);
        ErrorHandler::stackErrors();

        $this->logger->notice('Job exection started: {job}', [
            'job' => $this,
        ]);

        try {
            $instance = $this->getInstance();

            $event = new ResqueEvent($this->resque, [
                'job'      => $this,
                'instance' => $instance,
            ]);

            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }

            $this->events->dispatch(JobEvents::BEFORE_PERFORM, $event);

            if ($instance instanceof PerformInterface) {
                $instance->perform($this);
            } else {
                // TODO: handle oldschool jobs
            }

            $this->events->dispatch(JobEvents::AFTER_PERFORM, $event);

            $this->logger->notice('Job exection successful: {job}', [
                'job' => $this,
            ]);

            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown();
            }

            $success = true;
        } catch (Exception $e) {
            $this->fail($e);
        }

        ErrorHandler::unstackErrors();
        restore_exception_handler();

        return $this;
    }

    /**
     * Handles a job failure and stores a record in Redis
     *
     * @param $exception Exception  exception object which caused the failure
     *
     * @return Job
     */
    public function fail(Exception $exception)
    {
        $this->logger->debug(__METHOD__);

        $failure = new Failure($this->resque);

        $failure
            ->setPayload($this->resque->normalize($this))
            ->setError($exception->getMessage())
            ->setException($exception)
            ->setBacktrace($exception->getTraceAsString())
            ->setWorker($this->worker)
            ->setQueue($this->queue)
        ;

        $event = new ResqueEvent($this->resque, [
            'job'       => $this,
            'exception' => $exception,
            'failure'   => $failure,
        ]);

        $this->events->dispatch(JobEvents::ON_FAILURE, $event);

        $this->logger->error('Job execution failed: {error}', [
            'exception' => $failure->getException(),
            'error'     => $failure->getError(),
            'failure'   => $failure,
            'job'       => $this,
        ]);

        $item = $this->resque->normalize($failure);
        $this->storage->failurePush($item['queue'], $item);

        return $this;
    }

    /**
     * Creates an identical job, essentially placing this job back on
     * the queue
     *
     * @return Job
     */
    public function recreate()
    {
        $job = clone $this;
        $job->create();
        return $job;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Set the name of the queue this job lives on
     *
     * @param string $queue
     *
     * @return Job
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Get the name of the queue this job lives on
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the class name this job will run `perform()` on
     *
     * @param string $class
     *
     * @return Job
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Get the class name this job will run `perform()` on
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the job arguments passed to the perform class
     *
     * @param mixed $args
     *
     * @return Job
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Get the job arguments passed to the perform class
     *
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Set the worker that is currently performing this job
     *
     * @param Worker|null $worker
     *
     * @return Job
     */
    public function setWorker(Worker $worker = null)
    {
        $this->worker = $worker;
        return $this;
    }

    /**
     * Get the worker that is currently performing this job
     *
     * @return Worker|null
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Create a new perform class instance
     *
     * TODO: verify interface
     */
    public function getInstance()
    {
        if (!$this->instance) {
            $class = $this->getClass();
            $this->instance = new $class();
        }

        return $this->instance;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Set a runtime parameter for this job
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Job
     */
    public function setParameter($key, $value)
    {
        $this->parameters->offsetSet($key, $value);
        return $this;
    }

    /**
     * Get a runtime parameter for this job
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->parameters->offsetGet($key);
    }

    /**
     * Check if a given runtime parameter is set for this job
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter($key)
    {
        return $this->parameters->offsetExists($key);
    }

    /**
     * Get all runtime parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters->getArrayCopy();
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Normalizes the object into an array of scalars|arrays
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        $event = new ResqueEvent($this->resque, [
            'job'  => $this,
            'item' => [],
            'context' => $context,
            'normalizer' => $normalizer,
        ]);

        $this->events->dispatch(JobEvents::BEFORE_NORMALIZE, $event);

        $event['item'] = [
            'class' => $this->getClass(),
            'args'  => [$this->getArgs()],
        ];

        $this->events->dispatch(JobEvents::AFTER_NORMALIZE, $event);

        return $event['item'];
    }

    /**
     * Denormalizes the object back from an array of scalars|arrays
     */
    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = [])
    {
        $event = new ResqueEvent($this->resque, [
            'job'   => $this,
            'item'  => $data,
            'queue' => $context['queue'],
            'context' => $context,
            'denormalizer' => $denormalizer,
        ]);

        $this->events->dispatch(JobEvents::BEFORE_DENORMALIZE, $event);

        $this->class = $event['item']['class'];
        $this->args  = $event['item']['args'][0] ?: [];

        $this->queue = $event['queue'];

        $this->events->dispatch(JobEvents::AFTER_DENORMALIZE, $event);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->resque->normalize($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $item = $this->resque->normalize($this);

        return sprintf(
            '(%s: queue="%s", %s)',
            get_called_class(),
            $this->getQueue(),
            implode(', ', array_map(function ($key, $value) {
                return $key . '=' . json_encode($value);
            }, array_keys($item), $item))
        );
    }
}
