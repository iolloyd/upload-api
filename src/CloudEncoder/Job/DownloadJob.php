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

class DownloadJob extends AbstractJob 
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('job:encoder:download')
            ->setDescription('Allows fetching of a video file from a given url or path')

            ->addArgument('input',  InputArgument::REQUIRED, 'The video url to download from')
            ->addArgument('output', InputArgument::REQUIRED, 'The local path to save to')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile  = $input->getArgument('input');
        $outfile = $input->getArgument('output');
        $client  = new Client();
        $result  = $client->get($infile, [
            'save_to' => $outfile,
        ]);

        return $result;

    }
}

