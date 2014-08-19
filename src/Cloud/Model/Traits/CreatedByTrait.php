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

trait CreatedByTrait
{
    /**
     * #JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Cloud\Model\User")
     * @JMS\Groups({"list", "details"})
     * @JMS\ReadOnly
     * @CX\CreatedBy
     */
    protected $createdBy;

    /**
     * Set the user who created the model
     *
     * @param  User $user
     * @return CreatedByTrait
     */
    public function setCreatedBy(User $user)
    {
        $this->createdBy = $user;
        return $this;
    }

    /**
     * Get the user who created the model
     *
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
