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

class ThumbnailFilter implements VideoFilterInterface
{
    public function __construct($videoFile, $duration, $amount = 10, $priority = 0)
    {
        $this->fps       = floor($duration / $amount * 1000);
        $this->videoFile = $videoFile;
        $this->priority  = $priority;
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
        $commandString = 'fps=fps=%s thumb%%d.jpg';

        return [
            '-f',
            'image2',
            '-vf',
            sprintf('fps=fps=%s/60', $this->fps),
            'thumb%d.jpg',
        ];
    }
}

