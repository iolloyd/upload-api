<?php
namespace Tests\Mock;

class MockVideoInbound
{
    public static function get()
    {
        $videoInbound = new \Cloud\Model\VideoInbound();
        $videoInbound->setStatus('pending');

        return $videoInbound;
    }
}

