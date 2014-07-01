<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
<<<<<<< HEAD
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
=======
 * @copyright 2014 ReallyUsefulLimited
 * @license   Proprietary
>>>>>>> Refactoring
 */

namespace Cloud\Model;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Category extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\SlugTrait;

    /**
     * @ORM\Column(type="string")
     * @JMS\Groups({"list", "details"})
     */
    protected $title;

<<<<<<< HEAD
=======

    public function __toString()
    {
        return (string) $this->id;
    }

>>>>>>> Refactoring
    /**
     * Gets the title
     *
     * @return string
     */
    public function getTitle()
    {
      return $this->title;
    }

    /**
     * @param string $title
     *
<<<<<<< HEAD
     * @return Tag
=======
     * @return Category
>>>>>>> Refactoring
     */
    public function setTitle($title)
    {
      $this->title = $title;
<<<<<<< HEAD
    }
=======
      return $this;
    }

>>>>>>> Refactoring
}

