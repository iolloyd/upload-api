<?php

namespace Cloud\Model\Traits;

/**
 * Trait for `$id` primary key field
 */
trait IdTrait
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
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

