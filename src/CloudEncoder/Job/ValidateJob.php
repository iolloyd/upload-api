<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright Copyright (C) 2014 Really Useful Limited
 * @license   Proprietary
 */

namespace CloudEncoder\Job;

use RuntimeException;
use Cloud\Job\AbstractJob;
use FFMpeg\FFProbe;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('job:encoder:validate')
            ->setDescription("Validates a video file and returns metadata")

            ->addArgument('input', InputArgument::REQUIRED, 'The video to validate')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile = $input->getArgument('input');

        if (!file_exists($infile)) {
            throw new RuntimeException('Could not find file: ' . $infile);
        }

        $ffprobe = FFProbe::create();
        
        // Make sure we can parse the file
        try {
            $streams      = $ffprobe->streams($infile);
            $videoStreams = $streams->videos();
            $audioStreams = $streams->audios();
        } catch (RuntimeException $e) {
            throw $e;
        }

        // Make sure we have at least one video stream
        if (!$videoStreams) {
            throw new RuntimeException('Could not find a video stream for file: ' . $infile);
        }

        $output = [
            'video' => [],
            'audio' => [],
        ];

        foreach ($videoStreams as $stream) {
            $metadata = $stream->all();
            
            // Skip anything that is too short.
            if ($metadata['duration_ts'] <= 1) {
                continue;
            }

            $output['video'][] = $metadata;
        }

        if (!count($output['video'])) {
            throw new RuntimeException('Could not find a video stream for file ' . $infile);
        }

        foreach ($audioStreams as $stream) {
            $output['audio'][] = $stream->all();
        }

        print_r($output);
    }
}

