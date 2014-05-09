<?php
namespace CloudTest\Mock;

class MockVideoOutbound
{
    public static function get()
    {
        $videoOutbound = new \Cloud\Model\VideoOutbound();

        return $videoOutbound;
    }
}

