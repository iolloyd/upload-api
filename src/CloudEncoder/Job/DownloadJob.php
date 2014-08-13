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
use GuzzleHttp\Client;
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
                new InputArgument('input',  InputArgument::REQUIRED, 'The url of the video to download'),
                new InputArgument('output', InputArgument::REQUIRED, 'The local path to store the video'),
            ])
            ->setName('job:encoder:download')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile = $input->getArgument('input');
        $outfile = $input->getArgument('output');
        $client = new Client();
        $result = $client->get($infile, [ 'save_to' => $outfile]);

        return $result;
    }
}

