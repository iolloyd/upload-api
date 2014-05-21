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
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\Stat;

/**
 * Load test companies and test users
 */
class StatFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $stat = new Stat();
        $stat->setPlays(rand(10, 1000));
        $stat->setClicks(rand(100, 10000));
        $stat->setRating(rand(1, 100));
        $stat->setVideo(
            $this->getReference('video')
        );


        $em->persist($stat);
        $em->flush();
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\VideoFixture', 
        ];
    }


}
