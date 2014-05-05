<?php
namespace Tests;

use Cloud\Model\Site;
use Tests\Mock\MockSite;
use Tests\Mock\MockVideoOutbound;

class SiteTest extends Model
{
    public function testAddVideoOutbound()
    {
        $em = $this->entityManager;
        $site = MockSite::get();
        $videoOutbound = MockVideoOutbound::get();
        $site->addVideoOutbound($videoOutbound);
        $em->persist($site);
        $em->flush();

        $videoOutbounds = $site->getVideoOutbounds();

        $expected = $videoOutbound;
        $actual = $videoOutbounds[count($videoOutbounds) - 1];

        $this->assertEquals($expected, $actual);
    }


    public function testSave()
    {
        $em = $this->entityManager;
        $site = MockSite::get();
        $em->persist($site);
        $em->flush();
        $sites = $em->getRepository(
            "Cloud\Model\Site")->findAll();

        $this->assertEquals(1, count($site));
    }
}

