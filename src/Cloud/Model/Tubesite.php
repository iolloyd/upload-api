<?php

namespace Cloud\Model;

/**
 * Represents a tube-site and its configuration
 *
 * @Entity
 * @HasLifecycleCallbacks
 */
class Tubesite extends AbstractModel
{
    use \Gedmo\Timestampable\Traits\TimestampableEntity;
    use Traits\IdTrait;
    use Traits\SlugTrait;

    /**
     * @Column(type="string")
     */
    protected $title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $url;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $loginUrl;

    /**
     * @Column(type="string", nullable=true)
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
     * @param  string $login_url
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
     * @param  string $signup_url
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
