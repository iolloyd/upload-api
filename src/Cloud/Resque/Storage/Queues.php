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

class Queues extends AbstractStorage
{
    /**
     * Get a list of know queues
     *
     * @return array
     */
    public function all()
    {
        return (array) $this->redis()->smembers($this->prefix('queues'));
    }

    /**
     * Deletes a queue and all its queued items
     *
     * @param  string $queue
     * @return Queues
     */
    public function removeQueue($queue)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this
            ->redis()
            ->multi(Redis::PIPELINE)
                ->srem($this->prefix('queues'), $queue)
                ->del($this->prefix('queue:' . $queue))
            ->exec();

        return $this;
    }
}
