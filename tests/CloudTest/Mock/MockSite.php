<?php
namespace CloudTest\Mock;

class MockSite
{
    public static function get()
    {
        $site = new \Cloud\Model\TubeSite();
        $site->setTitle('I am a tube site');

        return $site;
    }
}

