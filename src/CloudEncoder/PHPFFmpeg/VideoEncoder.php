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
    public function process($videoFile, array $watermarkInfo = [])
    {
        $ffmpeg = FFMpeg::create()->open($videoFile);

        if (isset($watermarkInfo['watermark'])) {
            $ffmpeg->addFilter(new WatermarkFilter($watermarkInfo));
        }

        // TODO For testing only
        $output = 'marked.mp4';
        unlink($output);

        $result = $ffmpeg->save(new X264(), $output);

        return $result;

    }

}

