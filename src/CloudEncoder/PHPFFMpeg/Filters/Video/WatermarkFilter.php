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

namespace CloudEncoder\PHPFFmpeg\Filters\Video;

use InvalidArgumentException;
use FFMpeg\Format\VideoInterface;
use FFMpeg\Filters\Video\VideoFilterInterface;
use FFMpeg\Media\Video;
use PHPImageWorkshop\ImageWorkshop;

class WatermarkFilter implements VideoFilterInterface
{
    protected $params;
    protected $priority;
    protected $videoWidth;
    protected $videoHeight;

    /**
     * @param array $this->params
     * @param int   $priority
     */
    public function __construct(array $params, $priority = 0)
    {
        $this->params   = $params;
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Video          $video
     * @param VideoInterface $format
     * @return array
     */
    public function apply(Video $video, VideoInterface $format)
    {
        $watermarkPath = $this->prepareWatermark($video);
        $commandString = 'movie=%s [watermark]; [in][watermark] overlay=%s:%s [out]';

//        echo sprintf($commandString, $watermarkPath, $this->params['watermark_left'], $this->params['watermark_top']); //die;

        return [
            '-vf',
            sprintf($commandString, $watermarkPath, $this->params['watermark_left'], $this->params['watermark_top']),
        ];
    }

    /**
     * @param Video $video
     * @return string
     */
    protected function prepareWatermark(Video $video)
    {
        $input = $this->params['watermark_input'];
        
        // If all our values are absolute pixels, we leave the watermark file unchanged
        // and return the original watermark file intact
        
        if ($this->params['watermark_width']     && !$this->isPercent($this->params['watermark_width'])
            && $this->params['watermark_height'] && !$this->isPercent($this->params['watermark_height'])
            && $this->params['watermark_top']    && !$this->isPercent($this->params['watermark_top'])
            && $this->params['watermark_height'] && !$this->isPercent($this->params['watermark_height'])
        ) {
            return $input;
        }

        $resizedWm        = ImageWorkshop::initFromPath($input);
        $originalWmWidth  = $resizedWm->getWidth();
        $originalWmHeight = $resizedWm->getHeight();

        $videoDimensions   = $video->getStreams()->first()->getDimensions();
        $this->videoWidth  = $videoDimensions->getWidth();
        $this->videoHeight = $videoDimensions->getHeight();

        $newWmWidth  = null;
        $newWmHeight = null;

        /*
         * First, we need to ensure the size of the watermark is correct.
         *
         * If the top and bottom settings are percentages we need to resize the height.
         * If the left and right settings are percentages we need to resize the width.
         */

        if ($this->params['watermark_left'] && $this->params['watermark_right'] ) {

            if ($this->params['watermark_width']) {
                throw new InvalidArgument('It is not possible to specify width with left and right values');
            }

            $newWmWidth   = $this->videoWidth - $this->asPixels('left') - $this->asPixels('right');
        }

        if ($this->params['watermark_top'] && $this->params['watermark_bottom']) {
            if ($this->params['watermark_height']) {
                throw new InvalidArgument(
                    'It is not possible to specify height with top and bottom values'
                );
            }

            $topAndBottom = $this->asPixels('top') + $this->asPixels('bottom');
            $newWmHeight  = $this->videoHeight - $topAndBottom; 
        }

        /*
         * Now we need to adjust the dimensions and aspect ratio.
         *
         * If both the width and height have been set through percentage adjustment, then 
         * we do not worry about the aspect ratio.
         *
         * If the width has been changed and the height has not been set
         * we adjust the height to maintain the aspect ratio.
         *
         * If the height has been changed and the width has not been set
         * we adjust the width to maintain the aspect ratio.
         */
         if ($newWmWidth && !$newWmHeight) {
             
             // The height needs to change in ratio to the change in width
             $newWmHeight = $originWmHeight * ($newWmWidth / $originalWmWidth);
         }

        if ($newWmHeight && !$newWmWidth) {
             // The width needs to change in ratio to the change in height 
             $newWmWidth = $originalWmWidth * ($newWmHeight / $originWmHeight);
        }

        /*
         * Now we need to set the top and left coordinates.
         */

        if ($this->params['watermark_top']) {
            $this->params['watermark_top'] = $this->asPixels('top');

        } elseif ($this->params['watermark_bottom']) {
            $this->params['watermark_top'] = $this->videoHeight - $this->asPixels('bottom') - ($newWmHeight ?: $originWmHeight);
        } 

        if ($this->params['watermark_left']) {
            $this->params['watermark_left'] = $this->asPixels('left');

        } elseif ($this->params['watermark_right']) {
            $this->params['watermark_left'] = $this->videoWidth - $this->asPixels('right') - ($newWmWidth ?: $originalWmWidth);
        }

        $wmWidth  = $this->params['watermark_width']  ?: $newWmWidth  ?: $originalWmWidth;
        $wmHeight = $this->params['watermark_height'] ?: $newWmHeight ?: $originalWmHeight;

        /*
         * If we have only specified width or height, we need
         * to adjust by aspect ratio
         */

        if (($newWmHeight || $this->params['watermark_height'])
            && !$this->params['watermark_width']
        ) {
            $h = $newWmHeight ?: $this->params['watermark_height'];
            $wmWidth *= ($h / $originalWmHeight);
        }

        if (($newWmWidth || $this->params['watermark_width']) 
            && !$this->params['watermark_height']
        ) {
            $w = $newWmWidth ?: $this->params['watermark_width'];
            $wmHeight *= ($w / $originalWmWidth);
        }

        echo 'width: ' . $wmWidth . ' height: ' . $wmHeight . PHP_EOL;
die;
        $resizedWm->resizeInPixel($wmWidth, $wmHeight, false);

        /*
         * Make sure we normalize the top and left coordinates
         */
        if (!$this->params['watermark_left']) {
            if (!$this->params['watermark_right']) {
                $this->params['watermark_left'] = ($this->videoWidth  - $wmWidth)  / 2;
            } else {
                $this->params['watermark_left'] = $this->videoWidth  - $this->params['watermark_right'] - ($wmWidth / 2);
            }
        }

        if (!$this->params['watermark_top']) {
            $this->params['watermark_top'] = ($this->videoHeight - $wmHeight) / 2;
        }

        $savedPath = 'resized-' . $input;
        $resizedWm->save('.', $savedPath, true, null, 100);

        return $savedPath;
    }

    /**
     * @param $param
     * @return float
     */
    protected function asPixels($param)
    {
        $allowed = ['top', 'bottom', 'left', 'right'];

        if (!in_array($param, $allowed)) {
            throw new InvalidArgumentException("Unknown parameter: " . $param);
        }

        $param = 'watermark_' . $param;
        $paramValue = $this->params[$param];
        if (!$this->isPercent($paramValue)) {
            return $paramValue;
        }

        if (in_array($param, ['watermark_top', 'watermark_bottom'])) {
            return round($this->videoHeight / 100 * $paramValue);
        }

        return round($this->videoWidth / 100 * $paramValue);
    }

    /**
     * @param $number
     * @return bool
     */
    protected function isPercent($number)
    {
        return mb_substr($number, -1) == '%';
    }

}
