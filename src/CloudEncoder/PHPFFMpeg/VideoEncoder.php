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

namespace CloudEncoder\PHPFFmpeg;

use CloudEncoder\PHPFFmpeg\Filters\Video\WatermarkFilter;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;

/**
 * Class VideoEncoder
 *
 */
class VideoEncoder
{
    /**
     * @param       $videoFile
     * @param array $watermarkInfo
     * @return \FFMpeg\Media\Video
     */
    public function process($videoFile, array $watermarkParams = [], array $thumbnailParams = [])
    {
        $ffmpeg = FFMpeg::create()->open($videoFile);
        if (count($watermarkParams)) {
            $ffmpeg->addFilter(new WatermarkFilter($params['watermark']));
        }
        if (count($thumbnailParams)) {
            $ffmpeg->addFilter(new ThumbnailFilter($params['thumbnails']));
        }

        $output = 'transcoded/' . $videoFile;
        if (file_exists($output) {
            unlink($output);
        }

        $result = $ffmpeg->save(new X264(), $output);

        return $result;

    }

}

