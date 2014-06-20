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

use Cloud\Model\VideoFile;

/**
 * Loads all standard tubesites
 */
class VideoFileFixture extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $videoFile = new VideoFile();

        /*
        $videoFile->setVideo(
            $this->getReference('video')
        );
        */

        $videoFile->setAudioBitRate(343);
        $videoFile->setAudioChannels(3);
        $videoFile->setAudioCodec('mpg');
        $videoFile->setAudioSampleRate(1234);
        $videoFile->setContainerFormat('some-format');
        $videoFile->setDuration(1221);
        $videoFile->setFilename('video-filename');
        $videoFile->setFilesize(100);
        $videoFile->setFiletype('video/mpg');
        $videoFile->setFrameRate(123);
        $videoFile->setHeight(1024);
        $videoFile->setWidth(768);
        $videoFile->setMd5sum('alskdjfl');
        $videoFile->setResolution(121);
        $videoFile->setVideoBitRate(121);
        $videoFile->setVideoCodec('mpg');

        $em->persist($videoFile);
        $em->flush();

        $this->addReference('videoFile', $videoFile);
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\VideoFileFixture',
        ];
    }

}
