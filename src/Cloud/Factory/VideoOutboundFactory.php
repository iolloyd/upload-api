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


namespace Cloud\Factory;

use Cloud\Model\VideoInbound;
use Cloud\Model\VideoOutbound;

class VideoOutboundFactory
{
    public static function createFromInbound(VideoInbound $inbound)
    {
        $outbound = new VideoOutbound();
        /*
         * TODO remove after demo
         */
        $outbound->setExternalId($inbound->getExternalId());
        $outbound->setFilename($inbound->getFilename()); 
        $outbound->setFilesize($inbound->getFilesize());
        $outbound->setFiletype($inbound->getFiletype());

        return $outbound;
    }

}


