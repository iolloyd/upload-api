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


namespace Cloud\Model\DataFixtures\VideoFile;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Cloud\Model\VideoOutbound;
use Cloud\Model\VideoFile\OutboundVideoFile;

class OutboundVideoFileFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $outboundVideoFile = new OutboundVideoFile(
            $this->getReference('videoOutbound')
        );

        $outboundVideoFile
            ->setAudioBitRate(343)
            ->setAudioChannels(3)
            ->setAudioCodec('mpg')
            ->setAudioSampleRate(1234)
            ->setContainerFormat('some-format')
            ->setDuration(1221)
            ->setFilename('video-filename')
            ->setFilesize(100)
            ->setFiletype('video/mpg')
            ->setFrameRate(123)
            ->setHeight(1024)
            ->setVideoBitRate(121)
            ->setVideoCodec('mpg')
            ->setWidth(768);

        $em->persist($outboundVideoFile);
        $em->flush();

        $this->addReference('outboundVideoFile', $outboundVideoFile);
    }

    public function getDependencies()
    {
        return [
            'Cloud\Model\DataFixtures\VideoFixture',
            'Cloud\Model\DataFixtures\VideoOutboundFixture',
        ];
    }
}
