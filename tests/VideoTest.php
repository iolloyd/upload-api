<?php
use Cloud\Model\Video;
use Cloud\Model\Tag;

class VideoTest extends PHPUnit_Framework_TestCase
{
    public function testSetVideoTitle()
    {
        $video = new Video();
        $title = "the title";

        $video->setTitle($title);
        $this->assertEquals($title, $video->getTitle());
    }


    public function testAddVideoTag()
    {
        $video = new Video();
        $tag   = new Tag();

        $video->addTag($tag);
        $tags = $video->getTags();
        $this->assertEquals($tag, $tags[0]);
    }

}

