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

use FFMpeg\Filters\Video\VideoFilters as Original;

class VideoFilters extends Original
{
    /**
     * @param       $imagePath
     * @param array $coords
     * @return VideoFilters
     */
    public function watermark($imagePath, array $coords = [])
    {
        $this->media->addFilter(new WaterMarkFilter($imagePath, $coords));
        return $this;
    }
}

