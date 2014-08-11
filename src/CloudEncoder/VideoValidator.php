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

use FFMpeg\FFProbe;
use Exception;

/**
 * Class VideoValidator
 *
 */
class VideoValidator
{
    protected $videoFile;

    /**
     * @param $input
     * @return array
     * @throws \Exception
     */
    public function process($input)
    {
        $ffprobe = FFProbe::create();
        $video = $ffprobe->streams($input)->videos()->first();
        if ($video) {
            throw new Exception("Could not probe video: " . $input);
        }

        $audio = $ffprobe->streams($input)->audios()->first();

        $result = [
            'url'    => $input,
            'height' => $video->get('height'),
            'width'  => $video->get('width'),
            'format' => $video->get('pix_fmt'),
            'frame_rate'    => $video->get('r_frame_rate'),
            'video_codec'   => $video->get('codec_name'),
            'duration'      => $video->get('duration_ts'),
            'file_size'     => filesize($input),
            'channels'      => $audio->get('channels'),
            'audio_codec'   => $audio->get('codec_name'),
            'audio_bitrate' => $audio->get('bit_rate'),
            'audio_sample_rate' => $audio->get('sample_rate'),
        ];

        return $result;
    }
}

