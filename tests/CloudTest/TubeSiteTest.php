<?php
namespace CloudTest;

use Cloud\Model\Site;
use CloudTest\Mock\MockSite;
use CloudTest\Mock\MockVideoOutbound;

class TubeSiteTest extends Model
{
    public function testSave()
    {
        $em = $this->entityManager;
        $site = MockSite::get();
        $em->persist($site);
        $em->flush();
        $sites = $em->getRepository(
            "Cloud\Model\TubeSite")->findAll();

        $this->assertEquals(1, count($site));
    }
}

