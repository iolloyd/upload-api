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
use CloudEncoder\Transcoder;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Format\Video\X264;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Exception;

/**
 * Class TranscodeJob 
 */
class TranscodeJob extends AbstractJob
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('input',  InputArgument::REQUIRED, 'The video source'),
                new InputArgument('output', InputArgument::REQUIRED, 'The video destination'),
                
                // TODO possibly add these as options
                new InputArgument('width',  InputArgument::REQUIRED, 'The video width'),
                new InputArgument('height', InputArgument::REQUIRED, 'The video height'),
            ])
            ->setName('job:encoder:transcode')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile  = $input->getArgument('input');
        $outfile = $input->getArgument('output');
        $width   = $input->getArgument('width');
        $height  = $input->getArgument('height');

        $ffmpeg = FFMpeg::create()->open($infile);
        $ffmpeg->addFilter(new ResizeFilter(new Dimension($width, $height)));
        $ffmpeg->save(new X264(), $outfile);
    }
}

