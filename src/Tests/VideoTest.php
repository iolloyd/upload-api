<?php
namespace Tests;

use Cloud\Model\Video;
use Cloud\Model\Tag;

class VideoTest extends Model
{
    public function testSetVideoTitle()
    {
        $video = new Video();
        $title = "the title";

        $video->setTitle($title);
        $this->assertEquals($title, $video->getTitle());
    }


    public function testAddUserToVideo()
    {
        $video = $this->getVideo();
        $tag = new Tag();
        $video->addTag($tag);
        $tags = $video->getTags();
        $this->assertEquals($tag, $tags[0]);
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

