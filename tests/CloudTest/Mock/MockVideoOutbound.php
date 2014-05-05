<?php
namespace Tests\Mock;

class MockVideoOutbound
{
    public static function get()
    {
        $videoOutbound = new \Cloud\Model\VideoOutbound();
        $videoOutbound->setStatus('pending');

        return $videoOutbound;
    }
}

