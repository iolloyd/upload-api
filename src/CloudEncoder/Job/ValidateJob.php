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

use FFMpeg\FFProbe;
use Cloud\Job\AbstractJob;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * Class ValidateJob
 *
 * Used to check a video and return metadata
 */
class ValidateJob extends AbstractJob
{
    /**
     * Configures this job
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('input', InputArgument::REQUIRED, 'The url of the video to validate'),
            ])
            ->setName('job:encoder:validate')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile = $input->getArgument('input');

        $ffprobe = FFProbe::create();
        $streams = $ffprobe->streams($infile);
        $videoStreams = $streams->videos();
        $audioStreams = $streams->audios();
        $output = [
            'video' => [],
            'audio' => []
        ];

        if (!$videoStreams) {
            throw new Exception("Could not probe video: " . $infile);
        }

        foreach ($videoStreams as $stream) {
            $output['video'][] = $stream->all();
        }

        foreach ($audioStreams as $stream) {
            $output['audio'][] = $stream->all();
        }

        print_r($output);

    }
}

