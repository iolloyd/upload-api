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

use Cloud\Model\VideoOutbound;
use Cloud\Model\VideoOutboundFile;

/**
 * Loads all standard tubesites
 */
class VideoFileOutboundFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $videoOutboundFile = new VideoOutboundFile(
            $this->getReference('videoOutbound')
        );

        $outboundFile->setVideo(
            $this->getReference('video')
        );

        $videoOutboundFile->setAudioBitRate(343);
        $videoOutboundFile->setAudioChannels(3);
        $videoOutboundFile->setAudioCodec('mpg');
        $videoOutboundFile->setAudioSampleRate(1234);
        $videoOutboundFile->setContainerFormat('some-format');
        $videoOutboundFile->setDuration(1221);
        $videoOutboundFile->setFilename('video-filename');
        $videoOutboundFile->setFilesize(100);
        $videoOutboundFile->setFiletype('video/mpg');
        $videoOutboundFile->setFrameRate(123);
        $videoOutboundFile->setHeight(1024);
        $videoOutboundFile->setWidth(768);
        $videoOutboundFile->setMd5sum('alskdjfl');
        $videoOutboundFile->setResolution(121);
        $videoOutboundFile->setVideoBitRate(121);
        $videoOutboundFile->setVideoCodec('mpg');
;


        $em->persist($videoOutboundFile);
        $em->flush();

        $this->addReference('videoOutboundFile', $outbound);
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\VideoOutboundFixture',
        ];
    }

}
