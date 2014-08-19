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
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Format\Video\X264;
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

            ->addOption('width',  null, InputOption::VALUE_REQUIRED, 'The video width',  0)
            ->addOption('height', null, InputOption::VALUE_REQUIRED, 'The video height', 0)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile  = $input->getArgument('input');
        $outfile = $input->getArgument('output');
        $width   = $input->getOption('width');
        $height  = $input->getOption('height');
        $mode    = 'fit';
        $ffmpeg  = FFMpeg::create()->open($infile);

        /*
         * We have to ensure that both height and width are a positive
         * integer to avoid Dimension throwing an exception
         */
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

        $ffmpeg->save(new X264(), $outfile);
    }
}

