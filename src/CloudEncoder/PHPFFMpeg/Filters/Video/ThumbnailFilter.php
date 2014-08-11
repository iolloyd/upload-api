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

namespace CloudEncoder\PHPFFmpeg\Filters\Video;

use FFMpeg\Format\VideoInterface;
use FFMpeg\Filters\Video\VideoFilterInterface;
use FFMpeg\Media\Video;

/**
 * Class ThumbnailFilter
 *
 */
class ThumbnailFilter implements VideoFilterInterface
{
    /**
     * @param     $videoFile
     * @param     $duration
     * @param int $count
     * @param int $priority
     */
    public function __construct($count = 1, $firstFrame = true, $priority = 0)
    {
        $this->count      = $count;
        $this->firstFrame = $firstFrame;
        $this->priority   = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Video          $video
     * @param VideoInterface $format
     * @return array
     */
    public function apply(Video $video, VideoInterface $format)
    {
        $fpsCommand = sprintf("fps=fps=%.2f", $this->count / 60);
        return ['-f', 'image2', '-vf', $fpsCommand, 'thumb%d.jpg',];
    }
}

