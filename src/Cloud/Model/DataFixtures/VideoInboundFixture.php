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

use Cloud\Model\Video;
use Cloud\Model\VideoInbound;

/**
 * Loads all standard tubesites
 */
class VideoInboundFixture extends AbstractFixture implements DependentFixtureInterface   
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $inbound = new VideoInbound(
            $this->getReference('video')
        );

        $inbound->setToken('token12345');
        $inbound->setFilename('video inbound filename');
        $inbound->setCreatedBy(
            $this->getReference('user')
        );

        $em->persist($inbound);
        $em->flush();

        $this->addReference('video-inbound', $inbound);
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\VideoFixture', 
        ];
    }

}
