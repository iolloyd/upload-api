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
use CloudEncoder\S3VideoDownload;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                new InputArgument('output', InputArgument::REQUIRED, 'The local path to store the downloaded video'),
            ])
            ->setName('job:encoder:download')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app    = $this->getHelper('silex')->getApplication();
        $s3     = $app['aws']['s3'];
        $bucket = $app['config']['aws']['bucket'];
        $input  = $input->getArgument('input');
        $output = $input->getArgument('output');

        $downloader = new S3VideoDownload();
        $downloader->process($s3, $bucket, $input, $output);
    }
}

