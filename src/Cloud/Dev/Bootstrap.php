<?php

namespace Cloud\Dev;

use Cloud\Model\User;
use Cloud\Model\Video;
use Cloud\Model\Tag;

class Bootstrap 
{
    public static function createDevData($em)
    {
        $devData = self::getDevData();
        $users = self::createDevUsers($em, $devData['users']);
        $videos = self::createDevVideos($em, $devData['videos']);
        $tag = new Tag();
        $tag->setTitle('I am a tag');
        $em->persist($tag);
        $user = $users[0];
        //$users[0]->add($videos[0]);

        //$videos[0]->add($tag);
    }

    protected static function getDevData()
    {
        $app = \Slim\Slim::getInstance();
        $devUsers  = $app->config('dev.users');
        $devVideos = $app->config('dev.videos');

        return ['users' => $devUsers, 'videos' => $devVideos];
    }

    protected static function createDevUsers($em, $userData)
    {
        $userList = [];
        foreach ($userData as $info) {
            extract($info);
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($password);
            $em->persist($user);
            $userList[] = $user;
        }

        return $userList;
    }

    protected static function createDevVideos($em, $videoData)
    {
        $videoList = [];
        foreach ($videoData as $info) {
            extract($info);
            $video = new Video();
            $video->setPath($path);
            $video->setTitle($title);
            $video->setDescription($description);
            $em->persist($video);
            $videoList[] = $video;
        }
        return $videoList;
    }
}

