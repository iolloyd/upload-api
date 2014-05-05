<?php

namespace Cloud\Model;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Tag extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\SlugTrait;
    use TimestampableTrait;

    /**
     * @Column(type="string")
     */
    protected $title;

    /**
     * Set the tag name
     *
     * @param  string $title
     * @return Tag
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the tag name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}

