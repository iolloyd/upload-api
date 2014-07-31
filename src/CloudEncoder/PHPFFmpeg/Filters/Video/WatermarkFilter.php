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

class WatermarkFilter implements VideoFilterInterface
{
    protected $imagePath;
    protected $coords;
    protected $priority;

    public function __construct(array $watermarkInfo, $priority = 0)
    {
        $this->watermarkInfo = $watermarkInfo;
        $this->priority      = $priority;
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
        $info = $this->watermarkInfo;

        // Align either on the left or right
        $x = isset($info['left'])
            ? $info['left']
            : (isset($info['right'])
                ? sprintf('main_w - %d - overlay_w', $info['right'])
                : 0
            );

        // Align either at the top or bottom
        $y = isset($info['top'])
            ? $info['top']
            : (isset($info['bottom'])
                ? sprintf('main_h - %d - overlay_h', $info['bottom'])
                : 0
              );

        $commandString = 'movie=%s [watermark]; [in][watermark] overlay=%s:%s [out]';

        return [
            '-vf',
            sprintf($commandString, $info['watermark'], $x, $y),
        ];
    }
}
