<?php

namespace Cloud\Model\DataFixtures;

use Cloud\Model\TubesiteUser;
use Cloud\Model\Tubesite;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;


/**
 * Loads sample users without the credentials
 */
class TubesiteFixture extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $youporn = $this->getSite('youporn');
        $xhamster = $this->getSite('xhamster');

        $em->persist($youporn);
        $em->persist($xhamster);
        $em->flush();

        $this->addReference('youporn', $youporn);
        $this->addReference('xhamster', $xhamster);
    }

    protected function getSite($title)
    {
        $site = new Tubesite();
        $site->setTitle($title);
        $site->setDescription('I am description of '. $title);
        $site->setUrl($title . '/url');
        $site->setLoginUrl($title . '/login/url');

        return $site;
    }

    
}
