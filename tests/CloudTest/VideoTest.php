<?php
namespace CloudTest;

use Cloud\Model\Tag;
use Cloud\Model\Video;
use CloudTest\Mock\MockTag;
use CloudTest\Mock\MockVideo;
use CloudTest\Mock\MockVideoInbound;
use CloudTest\Mock\MockVideoOutbound;

class VideoTest extends Model
{
    public function testTimeStampable()
    {
        $now = new \DateTime("now");
        $video = MockVideo::get();
        $this->entityManager->persist($video);
        $this->entityManager->flush();
        $expected = $now->format(\DateTime::ISO8601);
        $actual = $video->getCreatedAt();

        $this->assertEquals($expected, $actual);
    }

    public function testSetVideoTitle()
    {
        $video = MockVideo::get();
        $title = "I am a title";
        $video->setTitle($title);
        $expected = $title;
        $actual = $video->getTitle();

        $this->assertEquals($expected, $actual);
    }


    public function testAddTag()
    {
        $video = MockVideo::get();
        $tag = MockTag::get();
        $video->addTag($tag);
        $tags = $video->getTags();
        $expected = $tag;
        $actual = $tags[0];

        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        $video = MockVideo::get();
        $em = $this->entityManager;
        $count1 = count($em->getRepository(
            "Cloud\Model\Video")->findAll());

        $em->persist($video);
        $em->flush();

        $count2 = count($em->getRepository(
            "Cloud\Model\Video")->findAll());

        $this->assertGreaterThan($count1, $count2);
    }

}

