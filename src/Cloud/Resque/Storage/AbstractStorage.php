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

namespace Cloud\Resque\Storage;

use Cloud\Resque\Resque;

/**
 * Storage classes are responsible for encoding and handling data between
 * Resque and Redis. They contain the commands send to redis.
 */
abstract class AbstractStorage
{
    /**
     * @var Resque
     */
    protected $resque;

    /**
     * Constructor
     */
    public function __construct(Resque $resque)
    {
        $this->resque = $resque;
    }

    /**
     * Get the Redis client for use with Resque
     *
     * @return Redis
     */
    public function redis()
    {
        return $this->resque->redis();
    }

    /**
     * Utility method to prefix the value with the prefix setting for phpredis
     *
     * @param  string $key
     * @return string
     */
    protected function prefix($key)
    {
        return $this->resque->prefix($key);
    }

    /**
     * Utility method to encode the given value for redis
     *
     * @param  array $value
     * @return string
     */
    protected function encode(array $value, array $context = [])
    {
        return $this->resque->encode($value, $context);
    }

    /**
     * Utility method to decode the given value from redis
     *
     * @param  string $value
     * @return array
     */
    protected function decode($value, array $context = [])
    {
        return $this->resque->decode($value, $context);
    }
}
