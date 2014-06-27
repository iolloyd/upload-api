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
use Cloud\Model\VideoStat;

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

        foreach (range(1, 5) as $x) {
            $video = new Video(
                $this->getReference('user')
            );

            $video->setTitle('Eye iz vidayo' . $x);
            $video->setDescription('Me iz dizcreyeber' . $x);
            $video->setCreatedBy(
                $this->getReference('user')
            );
            $video->setCompany(
                $this->getReference('cumulus')
            );
            $thumbnails = ['foo', 'bar', 'waz', 'kim', 'yas', 'bot', 'tir'];
            $video->setThumbnail(
                $thumbnails[rand(1, count($thumbnails)-1)].'.png'
            );

            $clicks = rand(100, 20000);
            $plays = rand($clicks*0.1, $clicks*0.6);
            $stat = new VideoStat();
            $stat->setPlays($plays);
            $stat->setClicks($clicks);
            $stat->setRating(rand(1, 100) / 100);
            $stat->setVideo($video);

            $video->setTitle('Test Video ' . $x);
            $video->setDescription('Description description description');
            $video->addCategory(
                $this->getReference('category-1')
            );

            $em->persist($video);
            $em->persist($stat);

            $this->addReference("video-" . $x, $video);
        }

        $em->flush();

    }

    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\UserFixture',
            __NAMESPACE__ . '\CategoryFixture',
        ];
    }

}
