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
use CloudEncoder\PHPFFmpeg\ThumbnailCreator;
use FFMpeg\FFProbe;
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
                new InputArgument('input',  InputArgument::REQUIRED, 'The video url to get thumbnails for'),
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
        $amount    = $input->getArgument('amount');
        $thumbnailCreator = new ThumbnailCreator();
        $thumbnailCreator->process($videoFile, $amount);
    }
}

