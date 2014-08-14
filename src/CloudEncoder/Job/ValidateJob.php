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
            ->setName('job:encoder:validate')
            ->addArgument('input', InputArgument::REQUIRED, 'The url of the video to validate')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $infile = $input->getArgument('input');
        if (!file_exists($infile)) {
            throw new Exception("Could not find file: " . $infile);
        }

        // Make sure we can parse the file
        try {
            $ffprobe      = FFProbe::create();
            $streams      = $ffprobe->streams($infile);
            $videoStreams = $streams->videos();
            $audioStreams = $streams->audios();
        } catch (RuntimeException $e) {
            throw $e;
        }

        // Make sure we have at least one video stream
        if (!$videoStreams) {
            throw new Exception("Could not find a video stream for file: " . $infile . PHP_EOL);
        }

        $output = [
            'video' => [],
            'audio' => [],
        ];

        foreach ($videoStreams as $stream) {
            $metadata = $stream->all();
            
            // Skip anything that is too short.
            // Some images have a recognized codec_type of video
            // but images only last a second at most
            if ($metadata['duration_ts'] <= 1) {
                continue;
            }

            $output['video'][] = $stream->all();
        }

        if (!$output['video']) {
            // Try to give a more helpful message by
            // checking if the selected file is an image
            $isImage = $this->isImage($infile);
            if ($isImage) {
                $message = 'Detected an image type for file ' . $infile;
            } else {
                $message = 'Could not find a video stream for file ' . $infile;
            }
            throw new Exception($message);
        }

        foreach ($audioStreams as $stream) {
            $output['audio'][] = $stream->all();
        }

        print_r($output);
    }

    protected function isImage($path)
    {
        $a = getimagesize($path);
        $type = $a[2];
        $acceptedTypes = [IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP];

        if (in_array($type, $acceptedTypes)) { 
            return true;
        }

        return false;
    }
}

