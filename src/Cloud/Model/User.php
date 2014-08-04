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

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Cloud\Model\Repository\UserRepository")
 */
class User extends AbstractModel implements AdvancedUserInterface, EquatableInterface
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(
     *   targetEntity="Company",
     *   fetch="EAGER",
     *   inversedBy="users"
     * )
     * @CX\Company(allowAnonymous=true)
     * @JMS\Groups({"details.user"})
     */
    protected $company;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"details.user", "details.session", "details.company"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true)
     * @JMS\Groups({"details.user", "details.session", "details.company"})
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @JMS\Exclude
     */
    protected $password;

    /**
     * #OneToMany(targetEntity="Video", mappedBy="created_by")
     * @JMS\Groups({"details.user"})
     */
    protected $videos;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({"details.user", "details.company"})
     */
    protected $lastLoginAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->videos = new ArrayCollection();
        $this->videoInbounds = new ArrayCollection();
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
     * @return array
     */
    public function getVideos()
    {
        $inbounds = $this->getVideoInbounds();
        return $inbounds;

        var_dump($inbounds); die;
        $videos = [];
        foreach ($this->getVideoInbounds() as $inbound) {
            $videos[] = $inbound;
        }

        return $videos;
    }

    /**
     * Get the video inbounds
     */
    public function getVideoInbounds()
    {
        return $this->videoInbounds;
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
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getEmail();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        $this->password = null;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool    true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool    true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool    true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool    true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * Also implementation should consider that $user instance may implement
     * the extended user interface `AdvancedUserInterface`.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        return $this->getUsername() == $user->getUsername();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getEmail();
    }
}

