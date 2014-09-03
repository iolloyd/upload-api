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

use InvalidArgumentException;
use CloudEncoder\PHPFFmpeg\Filters\Video\WatermarkFilter;
use CloudEncoder\PHPFFmpeg\Filters\Video\ThumbnailFilter;
use Cloud\Job\AbstractJob;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video as FFMpegVideo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranscodeJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('job:encoder:transcode')
            ->setDescription(
                'Provides the ability to process a video, including extracting
                thumbnails, adding watermarks and resizing the output video'
            )

            ->addArgument('input',  InputArgument::REQUIRED, 'The video source')
            ->addArgument('output', InputArgument::REQUIRED, 'The video destination')
        ;

        $this->configureThumbnails();
        $this->configureWatermark();
        $this->configureScaling();
    }

    protected function configureThumbnails()
    {
        $this
            ->addOption('thumbnail_first',    null, InputOption::VALUE_NONE,     'Whether to select from the first frame, default is false')
            ->addOption('thumbnail_number',   null, InputOption::VALUE_REQUIRED, 'Number of thumbnails to select evenly through video')
            ->addOption('thumbnail_interval', null, InputOption::VALUE_REQUIRED, 'Take thumbnails periodically')
            ->addOption(
                'thumbnail_times', 
                null, 
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 
                'Thumbnail by selected times'
            )
        ;

    }

    protected function configureWatermark() 
    {
        $this
            ->addOption('watermark_input',  null, InputOption::VALUE_REQUIRED, 'Watermark image')
            ->addOption('watermark_top',    null, InputOption::VALUE_REQUIRED, 'Top align in pixels')
            ->addOption('watermark_bottom', null, InputOption::VALUE_REQUIRED, 'Bottom align in pixels')
            ->addOption('watermark_left',   null, InputOption::VALUE_REQUIRED, 'Left align in pixels')
            ->addOption('watermark_right',  null, InputOption::VALUE_REQUIRED, 'Right align in pixels')
            ->addOption('watermark_width',  null, InputOption::VALUE_REQUIRED, 'Watermark width in pixels')
            ->addOption('watermark_height', null, InputOption::VALUE_REQUIRED, 'Watermark height in pixels')
        ;
    }

    protected function configureScaling()
    {
        $this
            ->addOption('width',  null, InputOption::VALUE_REQUIRED, 'Video width')
            ->addOption('height', null, InputOption::VALUE_REQUIRED, 'Video height')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $ffmpeg \FFMpeg\Media\Video */
        $ffmpeg = FFMpeg::create()->open($input->getArgument('input'));

        $this->executeThumbnails($ffmpeg, $input, $output);
        $this->executeWatermark($ffmpeg, $input, $output);
        $this->executeScaling($ffmpeg, $input, $output);

        $ffmpeg->save(new X264(), $input->getArgument('output'));
    }


    /**
     * @param FFMpegVideo     $ffmpeg
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function executeScaling(FFMpegVideo $ffmpeg, InputInterface $input, OutputInterface $output)
    {
        $width  = $input->getOption('width');
        $height = $input->getOption('height');
        $mode   = 'fit';

        if ($height && !$width) {
            $mode = 'width';
            $width = 1;

        } elseif ($width && !$height) {
            $mode = 'height';
            $height = 1;
        }

        if ($width > 0 && $height > 0) {
            $dimension = new Dimension($width, $height);
            $ffmpeg->addFilter(new ResizeFilter($dimension, $mode));
        }
    }

    protected function executeThumbnails(FFMpegVideo $ffmpeg, InputInterface $input, OutputInterface $output)
    {
        $params = [
            'thumbnail_number'   => $input->getOption('thumbnail_number'),
            'thumbnail_interval' => $input->getOption('thumbnail_interval'),
            'thumbnail_times'    => $input->getOption('thumbnail_times'),
            'thumbnail_first'    => $input->getOption('thumbnail_first'),
        ];
        $ffmpeg->addFilter(new ThumbnailFilter($params));
    }

    /**
     * @param FFMpegVideo     $ffmpeg
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function executeWatermark(FFMpegVideo $ffmpeg, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('watermark_input')) {
            $ffmpeg->addFilter(new WatermarkFilter($input->getOptions()));
        }
        return;
    }

}
