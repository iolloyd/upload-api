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
     * @param int $amount
     * @param int $priority
     */
    public function __construct($videoFile, $amount = 10, $priority = 0)
    {
        $this->amount    = $amount;
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
        $fpsCommand = sprintf('fps=fps=%f', number_format($this->amount / 60, 1));
        return ['-f', 'image2', '-vf', $fpsCommand, 'thumb%d.jpg',];
    }
}

