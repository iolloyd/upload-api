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
use Cloud\Model\VideoOutbound;

/**
 * Loads all standard tubesites
 */
class VideoOutboundFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $outbound = new VideoOutbound(
            $this->getReference('video-1')
        );

        $outbound->setStatus('complete');

        $outbound->setTubesite(
            $this->getReference('youporn')
        );

        $outbound->setTubesiteUser(
            $this->getReference('youporn-user')
        );

        $outbound->setCompany(
            $this->getReference('cumulus')
        );

        $outbound->setExternalId('12345');

        $em->persist($outbound);
        $em->flush();

        $this->addReference('videoOutbound', $outbound);
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\VideoFixture',
        ];
    }

}
