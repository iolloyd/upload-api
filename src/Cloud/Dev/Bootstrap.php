<?php

namespace Cloud\Dev;

use Cloud\Model\User;
use Cloud\Model\Video;
use Cloud\Model\Tag;

class Bootstrap 
{
    public static function createDevData()
    {
        $devData = self::getDevData();
        $users = self::createDevUsers($devData['users']);
        $videos = self::createDevVideos($devData['videos']);
        $tag = new Tag();
        $tag->title = 'I am a tag';
        $tag->save();
        $user = $users[0];
        $users[0]->add($videos[0]);
        $users[0]->save();

        $videos[0]->add($tag);
        $videos[0]->save();
    }

    protected static function getDevData()
    {
        $app = \Slim\Slim::getInstance();
        $devUsers  = $app->config('dev.users');
        $devVideos = $app->config('dev.videos');

        return ['users' => $devUsers, 'videos' => $devVideos];
    }

    protected static function createDevUsers($userData)
    {
        $userList = [];
        foreach ($userData as $info) {
            extract($info);
            $user = new User();
            $user->username = $username;
            $user->email    = $email;
            $user->password = $password;
            $user->save();
            $userList[] = $user;
        }

        return $userList;
    }

    protected static function createDevVideos($videoData)
    {
        $videoList = [];
        foreach ($videoData as $info) {
            extract($info);
            $video = new Video();
            $video->path = $path;
            $video->title = $title;
            $video->description = $description;
            $video->save();
            $videoList[] = $video;
        }
        return $videoList;
    }
}

