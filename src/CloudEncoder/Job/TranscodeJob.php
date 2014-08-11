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
                new InputArgument('input', InputArgument::REQUIRED, 'The video source'),
                new InputArgument('output', InputArgument::REQUIRED, 'The video destination'),

            ])
            ->addOption('watermark_input',  'w', InputOption::VALUE_REQUIRED, 'Watermark image')
            ->addOption('watermark_top',    't', InputOption::VALUE_REQUIRED, 'Top align')
            ->addOption('watermark_bottom', 'b', InputOption::VALUE_REQUIRED, 'Bottom align')
            ->addOption('watermark_left',   'l', InputOption::VALUE_REQUIRED, 'Left align')
            ->addOption('watermark_right',  'r', InputOption::VALUE_REQUIRED, 'Right align')
            ->addOption('thumbnails_count', 'c', InputOption::VALUE_REQUIRED, 'Total thumbnails')
            ->addOption('thumbnails_first_frame', 'a', InputOption::VALUE_NONE, 'If set, will start thumbnail collection at first frame')
            ->setName('job:encoder:transcode')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->checkWatermarkOptions($input);

            $params = array_filter($input->getOptions());
            $output->writeln('<info>Encoding ... </info>');

            $transcoder = new Transcoder();
            $transcoder->process($input->getArgument('input'), $input->getArgument('output'), $params);
            $output->writeln('<info>Done</info>');

        } Catch (Exception $e) {
            $output->writeln('<Error>'.$e->getMessage().'</Error>');
        }


    }

    /**
     * @param InputInterface $input
     * @return bool
     * @throws \Exception
     */
    protected function checkWatermarkOptions(InputInterface $input)
    {
        $wmInput  = $input->getOption('watermark_input');
        $wmTop    = $input->getOption('watermark_top');
        $wmBottom = $input->getOption('watermark_bottom');
        $wmLeft   = $input->getOption('watermark_left');
        $wmRight  = $input->getOption('watermark_right');
        $errors = [];

        if (!$wmInput) {
            return true;
        }

        if ($wmTop && $wmBottom) {
            $errors[] = 'You cannot set both top and bottom watermark points';
        }

        if ($wmLeft && $wmRight) {
            $errors[] = 'You cannot set both left and right watermark points';
        }

        if (!(($wmTop || $wmBottom)
            && ($wmLeft || $wmRight))
        ) {
            $errors[] = 'You must provide a top or bottom point and also a left or right point';
        }

        if (count($errors)) {
            $errorMessage = implode(',' . PHP_EOL, $errors);
            throw new Exception($errorMessage);
        }
    }
}

