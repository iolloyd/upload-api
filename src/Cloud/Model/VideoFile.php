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

namespace Cloud\Model;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="video_type", type="string")
 * @ORM\DiscriminatorMap({"video" = "Video","inbound" = "VideoFileInbound","template" = "VideoFileTemplate","outbound" = "VideoFileOutbound"})
 */
class VideoFile extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;

    const TYPE_INBOUND = 'inbound';
    const TYPE_OUTBOUND = 'outbound';
    const TYPE_TEMPLATE = 'template';

    const STATUS_COMPLETE = 'complete'; 
    const STATUS_ERROR    = 'error';
    const STATUS_PENDING  = 'pending';
    const STATUS_WORKING  = 'working'; 

    protected $video;


    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filename;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filesize;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filetype;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $duration;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $containerFormat;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $videoCodec;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $videoBitRate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $audioCodec;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $audioBitRate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $audioSampleRate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $audioChannels;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $height;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $width;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $resolution;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $frameRate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $aspectRatio;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $md5sum;

    /**
     * @param video
     */
    public function setVideo($video)
    {
        $this->video = $video;
    }

    /**
     * @return Video 
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * @return string
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string 
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;
    }

    /**
     *
     * @return string
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * @return string
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;
    }

    /**
     * @return string
     *
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * @param string
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param string
     */
    public function setContainerFormat($containerFormat)
    {
        $this->containerFormat = $containerFormat;
    }

    /**
     * @return string
     */
    public function getContainerFormat()
    {
        return $this->containerFormat;
    }

    /**
     * @param string
     */
    public function setVideoCodec($videoCodec)
    {
        $this->videoCodec = $videoCodec;
    }

    /**
     * @return string
     */
    public function getVideoCodec()
    {
        return $this->videoCodec;
    }

    /**
     * @param string
     */
    public function setVideoBitRate($videoBitRate)
    {
        $this->videoBitRate = $videoBitRate;
    }

    /**
     * @return string
     */
    public function getVideoBitRate()
    {
        return $this->videoBitRate;
    }

    /**
     * @param string
     */
    public function setAudioCodec($audioCodec)
    {
        $this->audioCodec = $audioCodec;
    }

    /**
     *  @return string
     */
    public function getAudioCodec()
    {
        return $this->audioCodec;
    }

    /**
     * @param string
     */
    public function setAudioBitRate($audioBitRate)
    {
        $this->audioBitRate = $audioBitRate;
    }

    /**
     * @return string
     */
    public function getAudioBitRate()
    {
        return $this->audioBitRate;
    }

    /**
     * @param string
     */
    public function setAudioSampleRate($audioSampleRate)
    {
        $this->audioSampleRate = $audioSampleRate;
    }

    /**
     * @return string
     */
    public function getAudioSampleRate()
    {
        return $this->audioSampleRate;
    }

    /**
     * @param string
     */
    public function setAudioChannels($audioChannels)
    {
        $this->audioChannels = $audioChannels;
    }

    /**
     * @return string
     */
    public function getAudioChannels()
    {
        return $this->audioChannels;
    }

    /**
     * @param string
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string
     */
    public function setResolution($resolution)
    {
        $this->resolution = $resolution;
    }

    /**
     * @return string
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    /**
     * @param string
     */
    public function setFrameRate($frameRate)
    {
        $this->frameRate = $frameRate;
    }

    /**
     * @return string
     */
    public function getFrameRate()
    {
        return $this->frameRate;
    }

    /**
     * @param string
     */
    public function setAspectRatio($aspectRatio)
    {
        $this->aspectRatio = $aspectRatio;
    }

    /**
     * @return string
     */
    public function getAspectRatio()
    {
        return $this->aspectRatio;
    }

    /**
     * @param string
     */
    public function setMd5sum($md5sum)
    {
        $this->md5sum = $md5sum;
    }

    /**
     * @return string
     */
    public function getMd5sum()
    {
        return $this->md5sum;
    }
}
