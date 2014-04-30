<?php
namespace Tests;

use Cloud\Model\Video;
use Cloud\Model\Tag;
use Tests\Mock\MockVideo;
use Tests\Mock\MockVideoInbound;
use Tests\Mock\MockVideoOutbound;

class VideoTest extends Model
{
    public function testSetVideoTitle()
    {
        $video = new Video();
        $title = "the title";

        $video->setTitle($title);
        $this->assertEquals($title, $video->getTitle());
    }


    public function testAddTag()
    {
        $video = $this->getVideo();
        $tag = new Tag();
        $video->addTag($tag);
        $tags = $video->getTags();
        $this->assertEquals($tag, $tags[0]);
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
        $video = $this->getVideo();
        $em = $this->entityManager;
        $em->persist($video);
        $em->flush();
        $videos = $em->getRepository(
            "Cloud\Model\Video")->findAll();

        $this->assertEquals(1, count($videos));
    }

    protected function getVideo()
    {
        $video = new Video();
        $video->setTitle('I am a video');
        $video->setFilename('I am a filename');
        $video->setStatus('pending');

        return $video;
    }


    protected function getTag()
    {
        $tag = new Tag();
        $tag->setTitle('I am a tag');

        return $tag;
    }

}

