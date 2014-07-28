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
use Cloud\Resque\Storage\AbstractStorage;

class StatusStorage extends AbstractStorage
{
    /**
     * Save the given status data for the given UUID in Redis
     *
     * Internally purges expired status objects
     *
     * @param string  $uuid
     * @param array   $item
     * @param integer $expireIn
     *
     * @return StatusStorage
     */
    public function set($uuid, array $item, $expireIn)
    {
        $this
            ->redis()
            ->multi(Redis::PIPELINE)
                ->setex($this->prefix('status:' . $uuid), $expireIn, $this->encode($item))
                ->zadd($this->prefix('_statuses'), time(), $uuid)
                ->zremrangebyscore($this->prefix('_statuses'), 0, time() - $expireIn)
            ->exec();

        return $this;
    }

    /**
     * Get the status data for the given UUID from Redis
     *
     * @param string $uuid
     *
     * @return array|null
     */
    public function get($uuid)
    {
        $item = $this->redis()->get($this->prefix('status:' . $uuid));

        if (!$item) {
            return null;
        }

        return $this->decode($item);
    }

    /**
     * Check if a kill flag is set for the given UUID
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function killFlagExists($uuid)
    {
        return $this->redis()->sismember($this->prefix('_kill'), $uuid);
    }

    /**
     * Remove the kill flag of the given UUID
     *
     * @param string $uuid
     *
     * @return StatusStorage
     */
    public function killFlagUnset($uuid)
    {
        $this->redis()->srem($this->prefix('_kill'), $uuid);
        return $this;
    }
}
