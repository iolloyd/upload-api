<?php

namespace Cloud\Model;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;

/**
 * Represents a tube-site and its configuration
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Tubesite extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\SlugTrait;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $url;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $loginUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $signupUrl;

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
     * Get the site name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the description
     *
     * @param  string $description
     * @return Tubesite
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the end user URL
     *
     * @param  string $url
     * @return Tubesite
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get the end user URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the upload portal URL
     *
     * @param  string $loginUrl
     * @return Tubesite
     */
    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;
        return $this;
    }

    /**
     * Get the upload portal URL
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * Set the CPP signup URL
     *
     * @param  string $signupUrl
     * @return Tubesite
     */
    public function setSignupUrl($signupUrl)
    {
        $this->signupUrl = $signupUrl;
        return $this;
    }

    /**
     * Get the CPP signup URL
     *
     * @return string
     */
    public function getSignupUrl()
    {
        return $this->signupUrl;
    }
}
