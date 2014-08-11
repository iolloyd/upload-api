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
use Exception;

/**
 * Class Transcoder 
 */
class Transcoder 
{
    /**
     * @param       $input
     * @param array $params
     * @throws \Exception
     */
    public function process($input, $output, array $params)
    {
        $ffmpeg = FFMpeg::create()->open($input);

        $watermarkParams = $this->paramsByPartialKey('watermark', $params);
        if (count($watermarkParams)) {
            $ffmpeg->addFilter(new WatermarkFilter($watermarkParams));
        }

        $thumbParams = $this->paramsByPartialKey('thumbnails', $params);
        if (count($thumbParams)) {
            $ffmpeg->addFilter(new ThumbnailFilter($thumbParams['count'], $thumbParams['first_frame']));
        }

        try {
            $ffmpeg->save(new X264(), $output);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $partialKey
     * @param $params
     * @return array
     */
    protected function paramsByPartialKey($partialKey, $params)
    {
        if (isset($params[$partialKey])) {
            return $params[$partialKey];
        }

        $filtered = array_filter(array_keys($params), function ($x) use ($partialKey) {
            return $partialKey == substr($x, 0, strlen($partialKey));
        });
        $filteredParams = array_intersect_key($params, array_flip($filtered));

        $mappedParams = [];
        foreach ($filteredParams as $key => $value) {
            $key = str_replace($partialKey . '_', '', $key);
            $mappedParams[$key] = $value;
        }

        return $mappedParams;
    }

}

