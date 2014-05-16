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

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Company extends AbstractModel implements JsonSerializable
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;

    /**
     * @ORM\Column(type="string")
     * @JMS\Groups({"list", "details", "list.companies", "details.companies"})
     */
    protected $title;

    /**
     * @ORM\OneToMany(
     *   targetEntity="User",
     *   mappedBy="company",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"list.companies", "details.companies"})
     */
    protected $users;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * Set the company name
     *
     * @param  string $title
     * @return Company
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the company name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add a user
     *
     * @param  User $user
     * @return Company
     */
    public function addUser(User $user)
    {
        $this->users->add($user);
        $user->setCompany($this);

        return $this;
    }

    /**
     * Remove a user
     *
     * @param  User $user
     * @return Company
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Get the users
     *
     * @return Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getTitle();
    }
}
