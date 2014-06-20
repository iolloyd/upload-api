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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class VideoFileOutbound extends VideoFile 
{
    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="VideoOutbound", inversedBy="videooutbounds")
     * @JMS\Groups({"details.videofileoutbound"})
     */
    protected $videoOutbound;
}
