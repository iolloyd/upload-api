<?php

namespace Cloud\Model\Traits;

use Gedmo\Mapping\Annotation\Timestampable;
use DateTime;

/**
 * Trait for `$created_at` field
 *
 * Entity must declare `@HasLifecycleCallbacks` 
 * for this trait to work as expected.
 */
trait TimestampableTrait
{
    /**
     * Get the created date
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get the last updated date
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @Column(type="datetime")
     * @\Gedmo\Mapping\Annotation\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @Column(type="datetime")
     * @\Gedmo\Mapping\Annotation\Timestampable(on="create")
     */
    protected $updatedAt;

}

