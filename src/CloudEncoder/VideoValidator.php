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

use CloudEncoder\PHPFFmpeg\Filters\Video\ThumbnailFilter;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;

/**
 * Class VideoValidator
 *
 */
class VideoValidator
{
    protected $videoFile;

    public function process($videoFile)
    {
        $ffprobe = FFProbe::create();
        $video   = $ffprobe->streams($videoFile)->videos()->first();
        $audio   = $ffprobe->streams($videoFile)->audios()->first();


        return [
            'url'    => $videoFile,
            'height' => $video->get('height'),
            'width'  => $video->get('width'),
            'format' => $video->get('pix_fmt'),
            'frame_rate'    => $video->get('r_frame_rate'),
            'video_codec'   => $video->get('codec_name'),
            'duration'      => $video->get('duration_ts'),
            'file_size'     => filesize($videoFile),
            'channels'      => $audio->get('channels'),
            'audio_codec'   => $audio->get('codec_name'),
            'audio_bitrate' => $audio->get('bit_rate'),
            'audio_sample_rate' => $audio->get('sample_rate'),
        ];
    }
}

