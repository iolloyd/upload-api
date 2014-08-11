<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace CloudEncoder;

use CloudEncoder\PHPFFmpeg\Filters\Video\ThumbnailFilter;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;

/**
 * Class ThumbnailCreator
 *
 */
class ThumbnailCreator 
{

    protected $videoFile;

    /**
     * @param      $videoFile
     * @param      $amount
     * @param null $output
     */
    public function process($videoFile, $amount, $output=null)
    {
        if (!$output) {
            $output = 'test_for_thumbs.mp4';
        }

        $ffmpeg   = FFMpeg::create()->open($videoFile);
        $ffprobe  = FFProbe::create();
        $video    = $ffprobe->streams($videoFile)->videos()->first();
        $duration = $video->get('duration');

        $ffmpeg->addFilter(new ThumbnailFilter($videoFile, $duration, $amount));
        $result = $ffmpeg->save(new X264(), $output);

        return $result;
    }
}

