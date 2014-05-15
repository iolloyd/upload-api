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

trait UpdatedAtTrait
{
    /**
     * @ORM\Column(type="datetime")
     * @CX\UpdatedAt
     */
    protected $updatedAt;

    /**
     * Set the updated at date
     *
     * @param  DateTime $updatedAt
     * @return UpdatedAtTrait
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get the updated at date
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
