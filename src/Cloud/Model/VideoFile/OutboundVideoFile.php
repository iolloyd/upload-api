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

use Cloud\Model\VideoOutbound;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class OutboundVideoFile extends AbstractVideoFile
{
    /**
     * @ORM\OneToOne(
     *   targetEntity="Cloud\Model\VideoOutbound",
     *   inversedBy="videoFile"
     * )
     */
    protected $outbound;

    /**
     * Constructor
     *
     * @param VideoOutbound $outbound  parent video outbound
     */
    public function __construct(VideoOutbound $outbound)
    {
        $this->setOutbound($outbound);
    }

    /**
     * Set the parent video outbound entity
     *
     * @param  VideoOutbound $outbound
     * @return OutboundVideoFile
     */
    public function setOutbound(VideoOutbound $outbound)
    {
        $this->outbound = $outbound;
        $this->setVideo($outbound->getVideo());
        return $this;
    }

    /**
     * Get the parent video outbound entity
     *
     * @return VideoOutbound
     */
    public function getOutbound()
    {
        return $this->outbound;
    }
}
