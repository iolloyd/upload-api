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
                new InputArgument('input', InputArgument::REQUIRED, 'The video location'),
            ])
            ->addOption('watermark_input',  'wi', InputOption::VALUE_REQUIRED, 'Watermark image')
            ->addOption('watermark_top',    'wt', InputOption::VALUE_REQUIRED, 'Top align')
            ->addOption('watermark_bottom', 'wb', InputOption::VALUE_REQUIRED, 'Bottom align')
            ->addOption('watermark_left',   'wl', InputOption::VALUE_REQUIRED, 'Left align')
            ->addOption('watermark_right',  'wr', InputOption::VALUE_REQUIRED, 'Right align')
            ->addOption('thumbnails_count', 'tc', InputOption::VALUE_REQUIRED, 'Total thumbnails')
            ->addOption('thumbnails_first_frame', 'tf', InputOption::VALUE_NONE, 'If set, will start thumbnail collection at first frame')
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
        $watermarkInfo = array_filter($input->getOptions());
        $output->writeln('<info>Encoding ... </info>');

        $videoFile = $input->getArgument('video');
        $videoEncoder = new VideoEncoder();
        $result = $videoEncoder->process($videoFile, $watermarkInfo);

        $output->writeln('<info>done</info>');

    }

}

