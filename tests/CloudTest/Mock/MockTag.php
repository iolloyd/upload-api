<?php
namespace Tests\Mock;

class MockTag
{
    public static function get()
    {
        $tag = new \Cloud\Model\Tag();
        $tag->setTitle('I am a tag');

        return $tag;
    }
}

