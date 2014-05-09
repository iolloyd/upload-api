<?php

namespace Cloud\Factory;

use Cloud\Model\VideoInbound;
use Cloud\Model\VideoOutbound;

class VideoOutboundFactory
{
    public static function create(VideoInbound $inbound)
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


