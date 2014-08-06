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
use CloudEncoder\VideoEncoder;
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
                new InputArgument('input',     InputArgument::REQUIRED, 'The url of the video to validate'),

            ])
            ->addOption('watermark', 'w', InputOption::VALUE_REQUIRED, 'Path of watermark image')
            ->addOption('top',       't', InputOption::VALUE_REQUIRED, 'Top aligned')
            ->addOption('bottom',    'b', InputOption::VALUE_REQUIRED, 'Bottom aligned')
            ->addOption('left',      'l', InputOption::VALUE_REQUIRED, 'Left aligned')
            ->addOption('right',     'r', InputOption::VALUE_REQUIRED, 'Right aligned')
            ->setName('job:encoder:encode')
        ;
    }

    /**
     * Executes this job
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $watermarkInfo = array_filter($input->getOptions());
        $output->writeln('<info>Encoding ... </info>');

        $videoFile = $input->getArgument('input');
        $watermarkImage = isset($watermarkInfo['watermark'])
            ? $watermarkInfo['watermark']
            : null;

        $result = $this->encode($videoFile, $watermarkInfo);

        $output->writeln('<info>done</info>');

    }

    /**
     * @param $videoFile
     */
    protected function encode($videoFile, $watermarkInfo)
    {
        $ffmpeg = FFMpeg::create()->open($videoFile);

        print_r($watermarkInfo); die;
        // Add the watermark
        if (isset($watermarkInfo['watermarkImage'])) {
            $ffmpeg->addFilter(new WatermarkFilter($watermarkInfo));
        }

        // Encode and save the video
        $result = 'watermarked-test.mp4';
        $ffmpeg->save(new X264(), $result);
    }
}

