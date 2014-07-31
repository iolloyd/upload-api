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
use CloudEncoder\PHPFFmpeg\VideoEncoder;
use CloudEncoder\PHPFFmpeg\Filters\Video\WatermarkFilter;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EncodingJob
 */
class EncodingJob extends AbstractJob
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('video', InputArgument::REQUIRED, 'The video location'),
            ])
            ->addOption('watermark', 'w', InputOption::VALUE_REQUIRED, 'Path of watermark image')
            ->addOption('top',       't', InputOption::VALUE_REQUIRED, 'Pixels aligned from top')
            ->addOption('bottom',    'b', InputOption::VALUE_REQUIRED, 'Pixels aligned from bottom')
            ->addOption('left',      'l', InputOption::VALUE_REQUIRED, 'Pixels aligned from left')
            ->addOption('right',     'r', InputOption::VALUE_REQUIRED, 'Pixels aligned from right')
            ->setName('job:encoder:encode')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $watermarkInfo = array_filter($input->getOptions());
        $output->writeln('<info>Encoding ... </info>');

        $videoFile = $input->getArgument('video');
        $videoEncoder = new VideoEncoder();
        $result = $videoEncoder->process($videoFile, $watermarkInfo);

        $output->writeln('<info>done</info>');

    }

}

