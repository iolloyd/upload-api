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
use CloudEncoder\PHPFFmpeg\Filters\Video\ThumbnailFilter;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ThumbnailJob
 */
class ThumbnailJob extends AbstractJob
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('input',  InputArgument::REQUIRED, 'The url of the video to get thumbnails for'),
                new InputArgument('amount', InputArgument::REQUIRED, 'The total number of thumbnails'),


            ])
            ->setName('job:encoder:thumbnails')
        ;
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $videoFile = $input->getArgument('input');
        $amount = $input->getArgument('amount');
        $this->thumbnails($videoFile, $amount);
    }

    /**
     * @param $videoFile
     */
    protected function thumbnails($videoFile, $amount)
    {
        $ffmpeg = FFMpeg::create()->open($videoFile);
        $ffprobe = FFProbe::create();
        $video   = $ffprobe->streams($videoFile)->videos()->first();
        $duration = $video->get('duration');

        // Add the watermark
        $ffmpeg->addFilter(new ThumbnailFilter($videoFile, $duration, $amount));

        // Encode and save the video
        $result = 'watermarked-test.mp4';
        $ffmpeg->save(new X264(), $result);
    }
}

