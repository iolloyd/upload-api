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

namespace Cloud\Model\VideoFile;

use Cloud\Model\VideoInbound;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class InboundVideoFile extends AbstractVideoFile
{
    /**
     * @ORM\OneToOne(
     *   targetEntity="Cloud\Model\VideoInbound",
     *   inversedBy="videoFile"
     * )
     */
    protected $inbound;

    /**
     * Constructor
     *
     * @param VideoInbound $inbound  parent video inbound
     */
    public function __construct(VideoInbound $inbound)
    {
        $this->setInbound($inbound);
    }

    /**
     * Set the parent video inbound entity
     *
     * @param  VideoInbound $inbound
     * @return InboundVideoFile
     */
    public function setInbound(VideoInbound $inbound)
    {
        $this->inbound = $inbound;
        $this->setVideo($inbound->getVideo());
        return $this;
    }

    /**
     * Get the parent video inbound entity
     *
     * @return VideoInbound
     */
    public function getInbound()
    {
        return $this->inbound;
    }
}
