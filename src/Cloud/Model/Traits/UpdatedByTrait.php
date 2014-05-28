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

namespace Cloud\Model\Traits;

use Cloud\Model\User;

trait UpdatedByTrait
{
    protected $updatedBy;

    /**
     * Set the updated at date
     *
     * @param  User $user
     * @return UpdatedAtTrait
     */
    public function setUpdatedBy(User $user)
    {
        $this->updatedBy = $user;
        return $this;
    }

    /**
     * Get the updated at date
     *
     * @return DateTime
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
