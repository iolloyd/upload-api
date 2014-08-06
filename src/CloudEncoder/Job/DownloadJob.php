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
use CloudEncoder\VideoDownload;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VideoEncoder
 *
 */
class DownloadJob extends AbstractJob 
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('input',  InputArgument::REQUIRED, 'The video url to get thumbnails for'),
            ])
            ->setName('job:encoder:download')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $videoFile = $input->getArgument('input');
        $downloader = new VideoDownload();
        $downloader->process($videoFile);
    }
}

