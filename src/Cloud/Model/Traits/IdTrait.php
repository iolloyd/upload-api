<?php

namespace Cloud\Model\Traits;

/**
 * Trait for `$id` primary key field
 */
trait IdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Get the ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}

