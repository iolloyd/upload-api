<?php

namespace Cloud\Model\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Cloud\Model\Video;

/**
 * Loads all standard tubesites
 */
class VideoFixture extends AbstractFixture implements DependentFixtureInterface   
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em)
    {
        $video = new Video(
            $this->getReference('user')
        );

        $video->setTitle('Eye iz vidayo');
        $video->setDescription('Me iz dizcreyeber');

        $em->persist($video);
        $em->flush();

        $this->addReference('video', $video);
    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\UserFixture', 
        ];
    }

}
