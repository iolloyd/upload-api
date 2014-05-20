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


namespace Cloud\Model;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Tag extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\SlugTrait;

    /**
     * @ORM\Column(type="string")
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

