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

use InvalidArgumentException;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Site extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\SlugTrait;
    use Traits\CompanyTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;

    /**
     * @ORM\Column(type="string")
     * @JMS\Groups({"list.sites", "details.sites", "details.session", "details", "list.tubesiteusers"})
     * @Assert\NotBlank
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true, length=7)
     * @JMS\Groups({"list.sites", "details.sites", "details.session", "details"})
     */
    protected $color;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list.sites", "details.sites", "details.session", "details"})
     */
    protected $initials;

    /**
     * Get the site name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the site name
     *
     * @param  string $title
     * @return Site
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the brand color as hex `#ffffff`
     *
     * @param  string $color
     * @return Site
     */
    public function setColor($color)
    {
        if (!preg_match('/^#?(?P<color>([0-9A-F]{3})([0-9A-F]{3})?)$/i', $color, $matches)) {
            throw new InvalidArgumentException('Invalid hex color');
        }

        $this->color = '#' . $matches['color'];

        return $this;
    }

    /**
     * Get the brand color as hex `#ffffff`
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the site initials which are shown as a short code
     *
     * @param  string $initials
     * @return Site
     */
    public function setInitials($initials)
    {
        $this->initials = $initials;
        return $this;
    }

    /**
     * Get the site initials which are shown as a short code
     *
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }
}

