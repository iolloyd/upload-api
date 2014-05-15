<?php

namespace Cloud\Model;

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Company extends AbstractModel implements JsonSerializable
{
    use \Gedmo\Timestampable\Traits\TimestampableEntity;
    use Traits\IdTrait;

    /**
     * @Column(type="string")
     */
    protected $title;

    /**
     * @OneToMany(
     *   targetEntity="User",
     *   mappedBy="company",
     *   cascade={"persist", "remove"}
     * )
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
