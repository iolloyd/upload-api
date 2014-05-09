<?php

namespace Cloud\Model\Traits;

use DateTime;

/**
 * Trait for `$createdAt` field
 */
trait TimestampableTrait
{
    /**
     * @Column(type="datetime")
     * @\Gedmo\Mapping\Annotation\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @Column(type="datetime")
     * @\Gedmo\Mapping\Annotation\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * Get created
     *
     * @return datetime $created
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the last updated date
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

}

