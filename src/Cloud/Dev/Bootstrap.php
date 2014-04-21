<?php

namespace Cloud\Dev;

use Cloud\Model\User;
use Cloud\Model\Video;

class Bootstrap 
{
    public static function createDevData()
    {
        $devdata = getDevData();
        self::createDevUsers($devData['users');
        self::createDevVideos($devData['videos']);
    }

    protected function getDevData()
    {
        $app = \Slim\Slim::getInstance();
        $devUsers  = $app->config('dev.users');
        $devVideos = $app->config('dev.videos');
        return [
            'users' => $devUsers,
            'videos' => $devVideos
        ]

    }

    protected static function createDevUsers($credentials)
    {
        foreach ($credentials as $info) {
            extract($info);
            $user = new User();
            $user->username = $username;
            $user->email    = $email;
            $user->password = $password;
            $user->save();
        }
    }

    protected static function createDevVideos($videos)
    {
        foreach ($videos as $info) {
            extract($info);
            $video = new Video();
            $video->path = $path;
            $video->title = $title;
            $video->description = $description;
            $video->save();
        }
    }
}

