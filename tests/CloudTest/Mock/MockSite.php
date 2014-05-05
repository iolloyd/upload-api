<?php
namespace Tests\Mock;

class MockSite
{
    public static function get()
    {
        $site = new \Cloud\Model\Site();
        $site->setTitle('I am a tube site');
        $site->setSlug('I am a slug');
        $site->setUploadUrl('I/am/url');

        return $site;
    }
}

