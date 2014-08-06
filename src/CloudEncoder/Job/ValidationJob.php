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

namespace CloudEncoder\Job;

use Cloud\Job\AbstractJob;
use CloudEncoder\VideoEncoder;
use FFMpeg\FFProbe;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidationJob extends AbstractJob
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('input', InputArgument::REQUIRED, 'The url of the video to validate'),
            ])
            ->setName('job:encoder:validate')
        ;
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Validating ... </info>');

        $videoFile     = $input->getArgument('input');
        $videoMetadata = $this->validate($videoFile);
        foreach ($videoMetadata as $key => $value) {
            $output->writeLn('<info>' . $key . ': ' . $value. '</info>');
        }

        $output->writeln('<info>done</info>');

    }

    /**
     * @param $input
     */
    protected function validate($videoFile)
    {
        $app = $this->getHelper('silex')->getApplication();
        $ffprobe = FFProbe::create();
        $video   = $ffprobe->streams($videoFile)->videos()->first();
        $audio   = $ffprobe->streams($videoFile)->audios()->first();

        return [
            "url"    => $videoFile,
            "height" => $video->get('height'),
            "width"  => $video->get('width'),
            "format" => $video->get('pix_fmt'),
            "frame_rate"    => $video->get('r_frame_rate'),
            "video_codec"   => $video->get('codec_name'),
            "duration"      => $video->get('duration_ts'),
            "file_size"     => filesize($videoFile),
            "video_bitrate" => $video->get('bit_rate'),
            "channels"      => $audio->get('channels'),
            "audio_codec"   => $audio->get('codec_name'),
            "audio_bitrate" => $audio->get('bit_rate'),
            "audio_sample_rate" => $audio->get('sample_rate'),
        ];
    }
}

