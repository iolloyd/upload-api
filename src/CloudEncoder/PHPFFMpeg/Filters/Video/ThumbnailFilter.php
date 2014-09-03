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
    public function __construct(array $params, $priority = 0)
    {
        $this->params = $params;
        $this->priority = $priority;
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
        $videoDuration = $video->getStreams()->videos()->first()->get('duration');

        // Avoid making duplicate thumbnails
        $times = array_unique(array_merge(
            $this->params['thumbnail_times'],
            $this->getIntervalsOrNumber($videoDuration)
        ));

        $commandArguments = [];

        // Since array_unique preserves the keys, we need 
        // to re-index the $times array
        foreach (array_values($times) as $index => $time) {
            $commandArguments = array_merge(
                $commandArguments, 
                ['-ss', $time, '-f', 'image2', '-vframes', '1', 'thumb'.$index.'.png']
            );
        }

        return $commandArguments;
    }

    /**
     * @param $videoDuration
     * @return array
     */
    protected function getIntervalsOrNumber($videoDuration)
    {
        // TODO later we will allow both 
        return (isset($this->params['thumbnail_interval']))
            ? $this->getIntervals($videoDuration)
            : $this->getNumber($videoDuration)
        ;
    }

    /**
     * @return array
     */
    protected function getIntervals($videoDuration)
    {
        if (!$this->params['thumbnail_interval']) {
            return [];
        }

        $interval = $this->params['thumbnail_interval'];
        $start    = isset($this->params['thumbnail_first']) ? 0 : $interval;
        $times    = range($start, $videoDuration, $interval);

        return $times;
    }

    /**
     * @param $videoDuration
     * @return array
     */
    protected function getNumber($videoDuration)
    {
        if (!$this->params['thumbnail_number']) {
            return [];
        }

        $divider  = $this->params['thumbnail_number'];
        if (!$this->params['thumbnail_first']) {
            ++$divider;
        }

        $period  = $videoDuration / $divider;
        $times = range(0, $videoDuration-1, $period);

        if (!$this->params['thumbnail_first']) {
            array_shift($times);
        }

        return $times;
    }
}

