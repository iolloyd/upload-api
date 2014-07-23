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

use JsonSerializable;
use Redis;
use RedisException;
use Cloud\Resque\Serializer\Normalizer\ResqueNormalizer;
use Cloud\Resque\Storage\Jobs as JobsStorage;
use Cloud\Resque\Storage\Queues as QueuesStorage;
use Cloud\Resque\Storage\Workers as WorkersStorage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;

/**
 * Resque management class
 */
class Resque
{
    const VERSION = '0.1.0';

    const DEFAULT_QUEUE = 'default';
    const DEFAULT_PREFIX = 'resque';
    const DEFAULT_INTERVAL = 5.0;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $events;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var JobsStorage
     */
    protected $jobs;

    /**
     * @var QueuesStorage
     */
    protected $queues;

    /**
     * @var WorkersStorage
     */
    protected $workers;

    // FIXME: hack
    public $redisHost = '127.0.0.1';

    /**
     * Constructor
     */
    public function __construct(LoggerInterface $logger = null, EventDispatcherInterface $events = null)
    {
        if (!$logger) {
            $logger = new NullLogger();
        }

        if (!$events) {
            $events = new EventDispatcher();
        }

        $this->logger  = $logger;
        $this->events  = $events;

        $this->jobs    = new JobsStorage($this);
        $this->queues  = new QueuesStorage($this);
        $this->workers = new WorkersStorage($this);
    }

    /**
     * Add a job to the default queue for the given class
     *
     * This method can be used to conveniently add a job to a queue. If the job
     * class you are passing declares its own default queue, it will be added
     * to it. Otherwise, the job will be queued on the default queue.
     *
     * @param  string|object $class  name or instance of the job class to queue
     * @param  mixed         $args   payload arguments
     *
     * @return void
     */
    public function enqueue($class, $args = null)
    {
        return $this->enqueueTo(
            //$this->queueFromClass($class),
            static::DEFAULT_QUEUE,
            $class,
            $args
        );
    }

    /**
     * Add a job to the given queue
     *
     * This method can be used to conveniently add a job to a queue. If the job
     * class you are passing declares its own default queue, it will be added
     * to it. Otherwise, the job will be queued on the default queue.
     *
     * @param  string        $queue  name of queue
     * @param  string|object $class  name or instance of the job class to queue
     * @param  mixed         $args   payload arguments
     *
     * @return void
     */
    public function enqueueTo($queue, $class, $args = null)
    {
        $job = new Job($this);

        $job->setQueue($queue)
            ->setClass($class)
            ->setArgs($args);

        $job->create();

        return $job;
    }

    //////////////////////////////////////////////////////////////////////////



    //////////////////////////////////////////////////////////////////////////

    /**
     * Get the logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the event dispatcher for Resque events
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Get the storage interface for resque jobs
     *
     * @return JobsStorage
     */
    public function getJobsStorage()
    {
        return $this->jobs;
    }

    /**
     * Get the storage interface for resque queues
     *
     * @return QueuesStorage
     */
    public function getQueuesStorage()
    {
        return $this->queues;
    }

    /**
     * Get the storage interface for resque workers
     *
     * @return WorkersStorage
     */
    public function getWorkersStorage()
    {
        return $this->workers;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Get the Redis client for use with Resque
     *
     * @return Redis
     */
    public function redis()
    {
        if (!$this->redis) {
            $this->redis = new Redis();

            // FIXME: review connection sharing between parent and forked
            //          child. this only works properly with pconnect
            // XXX:   connection timeout must be >= blpop timeout
            // TODO:  check if doctrine needs similar treatment:
            //          http://devlog.rolandow.com/2013/07/force-doctrine-to-close-mysql-connections/

            $result = $this->redis->pconnect($this->redisHost, 6379);

            if (!$result) {
                throw new \Exception('Redis connection failed');
            }

            $this->redis->setOption(Redis::OPT_PREFIX, rtrim(static::DEFAULT_PREFIX, ':') . ':');
        }

        return $this->redis;
    }

    /**
     * Ensure a working connection to Redis after forking the process
     *
     * @return Resque
     */
    public function ping()
    {
        // TODO: Fix the connect code above / remove this if not needed

        if (!$this->redis()->isConnected()) {
            $this->logger->debug('not connected to redis, reconnecting');
            $this->redis = null;
        }

        try {
            $this->redis()->ping();
        } catch (RedisException $e) {
            $this->logger->debug('lost redis, reconnecting; ' . $e->getMessage());
            $this->redis = null;
            $this->redis()->ping();
        }

        return $this;
    }

    /**
     * Utility method to prefix the value with the prefix setting for phpredis
     *
     * @param  string $key
     * @return string
     */
    public function prefix($key)
    {
        // return $this->redis()->_prefix($key);
        return $key;
    }

    /**
     * Utility method to encode the given value for redis
     *
     * @param  array $value
     * @return string
     */
    public function encode(array $value, array $context = [])
    {
        return $this->getSerializer()->encode($value, 'json', $context);
    }

    /**
     * Utility method to decode the given value from redis
     *
     * @param  string $value
     * @return array
     */
    public function decode($value, array $context = [])
    {
        return $this->getSerializer()->decode($value, 'json', $context);
    }

    /**
     * Utility method to turn the given object into a normalized array
     *
     * @param  object $object
     *
     * @return array
     */
    public function normalize(NormalizableInterface $object, array $context = [])
    {
        return $this->getSerializer()->normalize($object, 'json', $context);
    }

    /**
     * Utility method to turn the given array into a denormalized object
     *
     * @param  array  $data
     * @param  string $className
     *
     * @return object
     */
    public function denormalize(array $data, $className, array $context = [])
    {
        return $this->getSerializer()->denormalize($data, $className, 'json', $context);
    }

    /**
     * Get the serializer instance
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        if (!$this->serializer) {
            $this->serializer = new Serializer(
                [new ResqueNormalizer($this)],
                [new JsonEncoder()]
            );
        }

        return $this->serializer;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Generate a unique job ID
     *
     * @return string
     */
    public static function generateJobId()
    {
        return uniqid();
    }
}
