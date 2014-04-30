<?php
namespace Tests;

use Cloud\Model\Site;
use Tests\Mock\MockSite;

class SiteTest extends Model
{
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

