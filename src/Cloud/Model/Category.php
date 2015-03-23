<?php
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


    public function __toString()
    {
        return (string) $this->id;
    }

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
     * @return Tag
     * @return Category
     */
    public function setTitle($title)
    {
      $this->title = $title;

      return $this;
    }

}
