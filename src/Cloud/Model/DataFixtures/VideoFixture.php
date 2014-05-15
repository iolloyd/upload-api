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

        foreach (range(1, 30) as $x) {
            $video = new Video(
                $this->getReference('user')
            );

            $video->setTitle('Eye iz vidayo' . $x);
            $video->setDescription('Me iz dizcreyeber' . $x);
            $em->persist($video);
        }

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
