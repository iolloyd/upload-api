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

class Jobs extends AbstractStorage
{
    /**
     * Pushes an item onto a queue. Queue name should be a string and the
     * item should be any object.
     *
     * Resque works generally expect the `item` to be a hash with the following
     * keys:
     *
     *   class - string name of the job to run
     *    args - an array of arguments to pass the job
     *
     * @param  string $queue
     * @param  array  $item
     *
     * @return Jobs
     */
    public function push($queue, array $item)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this
            ->redis()
            ->multi(Redis::PIPELINE)
                ->sadd($this->prefix('queues'), $queue)
                ->rpush($this->prefix('queue:' . $queue), $this->encode($item))
            ->exec();

        return $this;
    }

    /**
     * Pops an item off a queue
     *
     * @param  string $queue
     *
     * @return array|null  decoded item or null if the queue was empty
     */
    public function pop($queue)
    {
        $item = $this->redis()->lpop($this->prefix('queue:' . $queue));

        if ($item === false) {
            return null;
        }

        return $this->decode($item);
    }

    /**
     * Wait and block until it pops an item off a queue
     *
     * @param  array   $queues   list of queue names to watch
     * @param  integer $timeout  maximum number of seconds to block
     *
     * @return array|null  an array of queue name and decoded item
     *                       or null on timeout
     */
    public function blpop(array $queues, $timeout = 0)
    {
        $queues = array_map(function ($d) {
            return $this->prefix('queue:' . $d);
        }, $queues);

        $data = $this->redis()->blpop($queues, (int) $timeout);

        if (empty($data)) {
            return null;
        }

        $queue = explode(':', $data[0]);
        $queue = array_pop($queue);

        $item  = $this->decode($data[1]);

        return [
            'queue' => $queue,
            'item'  => $item,
        ];
    }

    /**
     * Removes an item from a queue
     *
     * @param  string  $queue
     * @param  array   $item
     * @param  integer $count
     *
     * @return Jobs
     */
    public function rem($queue, array $item, $count = 1)
    {
        $this->redis()->lrem(
            $this->prefix('queue:' . $queue),
            $this->encode($item),
            $count
        );

        return $this;
    }

    /**
     * Returns an array of items currently queued without removing them
     *
     * @param string  $queue
     * @param integer $start
     * @param integer $count
     *
     * @return array
     */
    public function peek($queue, $start = 0, $count = 1)
    {
        if ($count == 1) {
            $list = [$this->redis()->lindex(
                $this->prefix('queue:' . $queue),
                $start
            )];
        } else {
            $list = $this->redis()->lrange(
                $this->prefix('queue:' . $queue),
                $start,
                $start + $count - 1
            );
        }

        return array_map([$this, 'decode'], $list);
    }

    /**
     * Counts the number of items on a queue
     *
     * @param  string $queue
     *
     * @return integer
     */
    public function size($queue)
    {
        return $this->redis()->llen($this->prefix('queue:' . $queue));
    }

    /**
     * Creates a new job failure and pushes it to the `failure` qeueue
     *
     * This is equivalent to the Ruby version
     * `Resque::Failure::Redis` and is the only "failure backend"
     * available.
     *
     * To handle failures differenty, subscribe to the
     * `onFailure` event and implement your own handler.
     *
     * @param  array $item
     * @return Jobs
     */
    public function failurePush($queue, array $item)
    {
        $this->redis()->rpush($this->prefix('failed'), $this->encode($item));
        return $this;
    }
}

