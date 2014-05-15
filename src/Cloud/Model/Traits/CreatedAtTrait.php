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

trait CreatedAtTrait
{
    /**
     * @ORM\Column(type="datetime")
     * @CX\CreatedAt
     */
    protected $createdAt;

    /**
     * Set the created at date
     *
     * @param  DateTime $createdAt
     * @return CreatedAtTrait
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the created at date
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
