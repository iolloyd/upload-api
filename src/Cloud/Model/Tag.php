<?php

namespace Cloud\Model;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Tag extends AbstractModel
{
    use \Gedmo\Timestampable\Traits\TimestampableEntity;
    use Traits\IdTrait;
    use Traits\SlugTrait;

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

