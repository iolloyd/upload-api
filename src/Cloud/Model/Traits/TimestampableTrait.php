<?php

namespace Cloud\Model\Traits;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

use DateTime;

/**
 * Trait for `$createdAt` field
 *
 * Entity must declare `@HasLifecycleCallbacks` 
 * for this trait to work as expected.
 */
trait TimestampableTrait
{
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="date")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @PrePersist
     */
    public function prePersistSetCreatedAt()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @PreUpdate
     */
    public function preUpdateSetUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

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

