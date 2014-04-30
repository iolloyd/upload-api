<?php
namespace Tests\Mock;
use Cloud\Model\Video;

class MockVideo
{
    public static function get()
    {
        $video = new Video();

        $video->setFilename('I am video filename');
        $video->setDescription('I am video desc');
        $video->setStatus('pending');
        $video->setTitle('I am title');

        $video->setCreator(MockUser::get());
        $video->addTag(MockTag::get());

        $video->addVideoInbound(MockVideoInbound::get());
        $video->addVideoOutbound(MockVideoOutbound::get());

        return $video;
    }
}

