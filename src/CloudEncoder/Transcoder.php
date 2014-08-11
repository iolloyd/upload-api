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

use CloudEncoder\PHPFFmpeg\Filters\Video\WatermarkFilter;
use CloudEncoder\PHPFFmpeg\Filters\Video\ThumbnailFilter;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;

/**
 * Class Transcoder 
 *
 */
class Transcoder 
{
   /**
     * @param       $videoFile
     * @param array $watermarkInfo
     * @return \FFMpeg\Media\Video
     */
    public function process($videoFile, array $params = [])
    {
        $ffmpeg = FFMpeg::create()->open($videoFile);

        $watermarkParams = $this->paramsByPartialKey('watermark', $params);
        if (count($watermarkParams)) {
            $ffmpeg->addFilter(new WatermarkFilter($watermarkParams));
        }

        $thumbnailParams = $this->paramsByPartialKey('thumbnails', $params);
        if (count($thumbnailParams)) {
            $ffmpeg->addFilter(new ThumbnailFilter($thumbnailParams));
        }

        // TODO For testing only
        $output = 'marked.mp4';
        if (file_exists($output)) {
            unlink($output);
        };

        $result = $ffmpeg->save(new X264(), $output);

        return $result;

    }

    /**
     * @param $partialKey
     * @param $params
     * @return array
     */
    protected function paramsByPartialKey($partialKey, $params)
    {
        $filtered = array_filter(array_keys($params), function ($x) use ($partialKey) {
                return $partialKey == substr($x, 0, strlen($partialKey));
            });
        $filteredParams = array_intersect_key($params, array_flip($filtered));

        return $filteredParams;
    }

}

