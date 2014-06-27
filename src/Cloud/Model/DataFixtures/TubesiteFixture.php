<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */


namespace Cloud\Model\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\Tubesite;

/**
 * Loads all standard tubesites
 */
class TubesiteFixture extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $youporn = $this->getYouPorn();
        $xhamster = $this->getXhamster();
        $xvideos = $this->getXVideos();

        $em->persist($youporn);
        $em->persist($xhamster);
        $em->persist($xvideos);
        $em->flush();

        $this->addReference('youporn', $youporn);
        $this->addReference('xhamster', $xhamster);
        $this->addReference('xvideos', $xvideos);
    }

    protected function getYouPorn()
    {
        $youporn = new Tubesite();
        $youporn->setTitle('YouPorn');
        $youporn->setDescription('YouPorn is a 100% free streaming adult video site hosting hundreds of thousands of videos from every genre imaginable that services more than 15 million visitors worldwide every day.');
        $youporn->setUrl('http://www.youporn.com/');
        $youporn->setLoginUrl('http://www.youporn.com/upload/');
        $youporn->setSignupUrl('http://www.youporn.com/contentpartnerprogram/getstarted/');

        return $youporn;
    }

    protected function getXhamster()
    {
        $xhamster = new Tubesite();
        $xhamster->setTitle('xHamster');
        $xhamster->setDescription('Now you can earn money with xHamster promoting your content (videos and photos) to xHamsters users (~20,000,000 unique visitors/day). Posting on xHamster is 100% free, we earn only when you earn with your revshare program.');
        $xhamster->setUrl('http://xhamster.com/');
        $xhamster->setLoginUrl('http://upload.xhamster.com/producer.php');
        $xhamster->setSignupUrl('http://xhamster.com/content_program.php');

        return $xhamster;
    }

    protected function getXVideos()
    {
        $xvideos = new Tubesite();

        $xvideos->setTitle('XVideos');
        $xvideos->setDescription('XVideos is a free pornographic video sharing website. It is the most popular pornographic website in the world, having overcome Pornhub as most popular adult video sharing website in November 2010.');
        $xvideos->setUrl('http://www.xvideos.com/');
        $xvideos->setLoginUrl('http://upload.xvideos.com/account');
        $xvideos->setSignupUrl('http://upload.xvideos.com/account/create');

        return $xvideos;
    }
}
