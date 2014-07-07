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

namespace Cloud\Model\Traits;

trait SiteTrait
{
    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Cloud\Model\Site")
     * @JMS\Groups({"details"})
     */
    protected $site;

    /**
     * Set the company site the entity belongs to
     *
     * @param  Site $site
     * @return SiteTrait
     */
    public function setSite(\Cloud\Model\Site $site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * Get the company site the entity belongs to
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

}
