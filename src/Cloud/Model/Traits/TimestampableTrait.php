<?php

namespace Cloud\Model\Traits;
use DateTime;

/**
 * Trait for `$created_at` field
 */
trait TimestampableTrait
{
    /**
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * Set the created date only the first time
     *
     * @PrePersist
     */
    public function prePersistSetCreatedAt()
    {
        if (empty($this->created_at)) {
            $now = date('Y-m-d H:i:s');
            $this->created_at = $now; 
        }
    }

    /**
     * Get the created date
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }


    /**
     * Set the updated date
     * @PreUpdate
     */
    public function preUpdateUpdatedAt()
    {
        $now = date('Y-m-d H:i:s');
        $this->updated_at = $date;
    }

    /**
     * Get the last updated date
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

}

