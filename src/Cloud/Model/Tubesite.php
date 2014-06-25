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
     * @JMS\Groups({"list", "details", "list.tubesites", "details.tubesites"})
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list.tubesites", "details.tubesites"})
     */
    protected $description;

    /**
     * @ORM\Column(type="string")
     * @JMS\Groups({"list.tubesites", "details.tubesites"})
     */
    protected $url;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list.tubesites", "details.tubesites"})
     */
    protected $loginUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list.tubesites", "details.tubesites"})
     */
    protected $signupUrl;

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
     * Get the upload portal URL
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
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
    public function getOrientation()
    {
        return $this->orientation;
    }

    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
    }

    /**
     * Set the short description based
     * on model data.
     */
    public function setShortDescription()
    {
        return sprintf('%s %s %s',
            $this->getTitle(),
            $this->getOrientation(),
            $this->getUrl()
        );

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
     * Get the end user URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
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

}
