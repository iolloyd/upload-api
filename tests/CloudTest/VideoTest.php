<?php
namespace Tests;

use Cloud\Model\Tag;
use Cloud\Model\Video;
use Tests\Mock\MockTag;
use Tests\Mock\MockVideo;
use Tests\Mock\MockVideoInbound;
use Tests\Mock\MockVideoOutbound;

class VideoTest extends Model
{
    public function testTimeStampable()
    {
        $video = MockVideo::get();
        $this->em->persist($video);
        $now = new \DateTime("now");
        $this->em->flush();
        $expected = $now;
        $actual = $video->getCreatedAt();

        $this->assertEquals($expected, $actual);
    }

    public function testSetVideoTitle()
    {
        $video = MockVideo::get();
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

    public function testVideoInbound()
    {
        $video = MockVideo::get();
        $inbound = MockVideoInbound::get();
        $inbound->setVideo($video);
        $inbounds = $video->getVideoInbounds();
        $expected = $inbound;
        
        // Make sure we get the last inserted
        $actual = $inbounds[count($inbounds)-1];

        $this->assertEquals($expected, $actual);
    }

    public function testVideoOutbound()
    {
        $video = MockVideo::get();
        $outbound = MockVideoOutbound::get();
        $outbound->setVideo($video);
        $outbounds = $video->getVideoOutbounds();
        $expected = $outbound;
        
        // Make sure we get the last inserted
        $actual = $outbounds[count($outbounds)-1];

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

