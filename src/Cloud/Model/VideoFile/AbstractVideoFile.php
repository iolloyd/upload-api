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

namespace Cloud\Model\VideoFile;

use Cloud\Model\AbstractModel;
use Cloud\Model\Traits;
use Cloud\Model\Video;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\Table(name="video_file")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "inbound"  = "Cloud\Model\VideoFile\InboundVideoFile",
 *     "template" = "Cloud\Model\VideoFile\TemplateVideoFile",
 *     "outbound" = "Cloud\Model\VideoFile\OutboundVideoFile"
 * })
 */
abstract class AbstractVideoFile extends AbstractModel
{
    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;
    use Traits\CompanyTrait;

    const STATUS_DRAFT    = 'draft';
    const STATUS_PENDING  = 'pending';
    const STATUS_WORKING  = 'working';
    const STATUS_ERROR    = 'error';
    const STATUS_COMPLETE = 'complete';

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Cloud\Model\Video")
     */
    protected $video;

    /**
     * @ORM\Column(type="string")
     * @JMS\Groups({"list", "details"})
     */
    protected $status = self::STATUS_DRAFT;

    // File

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filename;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filesize;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $filetype;

    // Container

    /**
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $duration;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $containerFormat;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $height;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $width;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $frameRate;

    // Video Codec

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $videoCodec;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $videoBitRate;

    // Audio Codec

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $audioCodec;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $audioBitRate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $audioSampleRate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $audioChannels;

    // Other

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $zencoderJobId;

    //////////////////////////////////////////////////////////////////////////

    /**
     * Set the parent video
     *
     * @param  Video $video
     * @return AbstractVideoFile
     */
    public function setVideo(Video $video)
    {
        $this->video = $video;
        $this->setCompany($video->getCompany());
        return $this;
    }

    /**
     * Get the parent video
     *
     * @return Video
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set the processing status
     *
     * @param  string $status
     * @throws \InvalidArgumentException
     * @return AbstractVideoFile
     */
    public function setStatus($status)
    {
        if (!in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_WORKING,
            self::STATUS_ERROR,
            self::STATUS_COMPLETE
        ])) {
            throw new \InvalidArgumentException("Invalid status");
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get the processing status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * @return VideoFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;
        return $this;
    }

    /**
     * @return string
     */
    public function getFiletype()
    {
        return $this->filetype;
    }

    /**
     * @param string
     *
     * @return VideoFile
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setContainerFormat($containerFormat)
    {
        $this->containerFormat = $containerFormat;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setVideoCodec($videoCodec)
    {
        $this->videoCodec = $videoCodec;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setVideoBitRate($videoBitRate)
    {
        $this->videoBitRate = $videoBitRate;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setAudioCodec($audioCodec)
    {
        $this->audioCodec = $audioCodec;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setAudioBitRate($audioBitRate)
    {
        $this->audioBitRate = $audioBitRate;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setAudioSampleRate($audioSampleRate)
    {
        $this->audioSampleRate = $audioSampleRate;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setAudioChannels($audioChannels)
    {
        $this->audioChannels = $audioChannels;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
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
     *
     * @return VideoFile
     */
    public function setFrameRate($frameRate)
    {
        $this->frameRate = $frameRate;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrameRate()
    {
        return $this->frameRate;
    }

    /**
     * Set the external ID of the Zencoder encoding job for this videofile
     *
     * @param  int $zencoderJobId
     * @return AbstractVideoFile
     */
    public function setZencoderJobId($zencoderJobId)
    {
        $this->zencoderJobId = $zencoderJobId;
        return $this;
    }

    /**
     * Get the external ID of the Zencoder encoding job for this videofile
     *
     * @return int
     */
    public function getZencoderJobId()
    {
        return $this->zencoderJobId;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Get the storage path for this video file
     *
     * The path is in the format `videofiles/{video}/{videofile}/{filename}`
     *
     * @return string
     */
    public function getStoragePath()
    {
        return sprintf(
            'videofiles/%d/%d/%s',
            $this->getVideo()->getId(),
            $this->getId(),
            $this->getFilename()
        );
    }
}
