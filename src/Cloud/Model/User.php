<?php

namespace Cloud\Model;

use DateTime;
use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class User extends AbstractModel implements JsonSerializable
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\TimestampableTrait;

    /**
     * @ManyToOne(
     *   targetEntity="Company",
     *   fetch="EAGER",
     *   inversedBy="users"
     * )
     * @JoinColumn(nullable=false)
     */
    protected $company;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @Column(type="string", unique=true)
     */
    protected $email;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $password;

    /**
     * #OneToMany(targetEntity="Video", mappedBy="created_by")
     */
    protected $videos;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $last_login_at;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->videos = new ArrayCollection();
    }

    /**
     * Set the company the user belongs to
     *
     * @param  Company $company
     * @return User
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Get the company the user belongs to
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the users full name
     *
     * @param  string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the users full name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the email address
     *
     * @param  string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get the email address
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the password
     *
     * The password will be stored as a `password_hash()` hash and cannot be
     * read directly. Use the `verifyPassword()` method to compare the passwords.
     *
     * @param  string $password
     * @return User
     */
    public function setPassword($password)
    {
        if (password_needs_rehash($password, PASSWORD_DEFAULT)) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->password = $password;

        return $this;
    }

    /**
     * Verify that the given password matches the stored hash
     *
     * @param  string $password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * Check if a password has been set for this user
     *
     * @return bool
     */
    public function hasPassword()
    {
        return (bool) $this->password;
    }

    /**
     * Get the videos created by this user
     *
     * @return Collection
     */
    public function getVideos()
    {
        return $this->users;
    }

    /**
     * Set the most recent login date
     *
     * @param  DateTime $last_login_at
     * @return User
     */
    public function setLastLoginAt($last_login_at = null)
    {
        if (!$last_login_at) {
            $last_login_at = new DateTime();
        }

        $this->last_login_at = $last_login_at;

        return $this;
    }

    /**
     * Get the most recent login date
     *
     * @return DateTime
     */
    public function getLastLoginAt()
    {
        return $this->last_login_at;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getEmail();
    }
}

