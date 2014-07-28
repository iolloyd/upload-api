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

use Redis;

class Workers extends AbstractStorage
{
    /**
     * Get a list of known workers
     *
     * @return array
     */
    public function all()
    {
        return (array) $this->redis()->smembers($this->prefix('workers'));
    }

    /**
     * Register a worker and set the starting time
     *
     * @param string $worker   id of the worker
     * @param string $started  starting time
     *
     * @return Workers
     */
    public function addWorker($worker, $started)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this
            ->redis()
            ->multi(Redis::PIPELINE)
                ->sadd($this->prefix('workers'), $worker)
                ->set($this->prefix('worker:' . $worker . ':started'), $started)
            ->exec();

        return $this;
    }

    /**
     * Remove a worker from Redis
     *
     * @param string $worker  id of the worker
     *
     * @return Workers
     */
    public function removeWorker($worker)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this
            ->redis()
            ->multi(Redis::PIPELINE)
                ->srem($this->prefix('workers'), $worker)
                ->del($this->prefix('worker:' . $worker))
                ->del($this->prefix('worker:' . $worker . ':started'))
            ->exec();

        return $this;
    }

    /**
     * Set the info about what the worker is currently processing
     *
     * @param string $worker  id of the worker
     * @param array  $item    process information to store
     *
     * @return Workers
     */
    public function setWorkingOn($worker, array $item)
    {
        $this->redis()->set($this->prefix('worker:' . $worker), $this->encode($item));
        return $this;
    }

    /**
     * Set that the working is done processing
     *
     * @param string $worker  id of the worker
     *
     * @return Workers
     */
    public function unsetWorkingOn($worker)
    {
        $this->redis()->del($this->prefix('worker:' . $worker));
        return $this;
    }

    /**
     * Check if the worker is currently processing
     *
     * @param string $worker  id of the worker
     *
     * @return bool
     */
    public function hasWorkingOn($worker)
    {
        return $this->redis()->exists($this->prefix('worker:' . $worker));
    }
}
