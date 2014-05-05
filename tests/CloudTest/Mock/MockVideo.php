<?php
namespace CloudTest\Mock;

use Cloud\Model\Video;

class MockVideo
{
    public static function get()
    {
        $video = new Video();

        $video->setFilename('I am video filename');
        $video->setDescription('I am a video description');
        $video->setStatus('pending');
        $video->setTitle('I am a video title');

        return $video;
    }
}

