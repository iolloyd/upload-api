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

use Cloud\Model\VideoFile\OutboundVideoFile;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class VideoOutbound extends AbstractModel
{
    const STATUS_PENDING  = 'pending';
    const STATUS_WORKING  = 'working';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR    = 'error';

    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\UpdatedAtTrait;
    use Traits\CompanyTrait;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Video", inversedBy="outbounds")
     * @JMS\Groups({"details.outbounds"})
     */
    protected $video;

    /**
     * @ORM\OneToOne(
     *   targetEntity="Cloud\Model\VideoFile\OutboundVideoFile",
     *   mappedBy="outbound"
     * )
     * @JMS\Groups({"details"})
     */
    protected $videoFile;

    /**
     * @see STATUS_PENDING
     * @see STATUS_WORKING
     * @see STATUS_COMPLETE
     * @see STATUS_ERROR
     *
     * @ORM\Column(type="string", length=16)
     * @JMS\Groups({"list", "details"})
     */
    protected $status = self::STATUS_PENDING;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Tubesite")
     * @JMS\Groups({"list", "details"})
     */
    protected $tubesite;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="TubesiteUser")
     * @JMS\Groups({"details"})
     */
    protected $tubesiteUser;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"details"})
     */
    protected $externalId;

    /**
     * @ORM\Column(type="json_array")
     * @JMS\Groups({"details"})
     */
    protected $params = [];

    // TODO:
    // transferStatus
    // submitStatus
    // approvalStatus

    /**
     * Constructor
     */
    public function __construct(Video $video = null, TubesiteUser $tubeuser = null)
    {
        if ($video) {
            $this->setVideo($video);
        }

        if ($tubeuser) {
            $this->setTubesite($tubeuser->getTubesite());
            $this->setTubesiteUser($tubeuser);
        }
    }

    /**
     * Set the processing status
     *
     * @param  string $status
     * @return VideoInbound
     */
    public function setStatus($status)
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_WORKING,
            self::STATUS_COMPLETE,
            self::STATUS_ERROR,
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

    /**
     * Set the parent video
     *
     * @param  Video $video
     * @return VideoOutbound
     */
    public function setVideo(Video $video)
    {
        $this->setCompany($video->getCompany());
        $this->video = $video;
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
     * Set the tubesite this outbound is for
     *
     * @param  Tubesite $tubesite
     * @return VideoOutbound
     */
    public function setTubesite(Tubesite $tubesite)
    {
        $this->tubesite = $tubesite;
        return $this;
    }

    /**
     * Get the tubesite this outbound is for
     *
     * @return Tubesite
     */
    public function getTubesite()
    {
        return $this->tubesite;
    }

    /**
     * Set the tubesite user to use for this outbound
     *
     * @param  TubesiteUser $tubesiteUser
     * @return VideoOutbound
     */
    public function setTubesiteUser(TubesiteUser $tubesiteUser)
    {
        $this->tubesiteUser = $tubesiteUser;
        return $this;
    }

    /**
     * Get the tubesite user to use for this outbound
     *
     * @return TubesiteUser
     */
    public function getTubesiteUser()
    {
        return $this->tubesiteUser;
    }

    /**
     * Set the parent company
     *
     * @param  Company $company
     * @return VideoInbound
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Set the parent company
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the external ID to identify the outbound on the tubesite
     *
     * @param  string $externalId
     * @return VideoOutbound
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * Get the external ID to identify the outbound on the tubesite
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set extra remote video parameters
     *
     * @param  array $params
     * @return VideoOutbound
     */
    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get extra remote video parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Add extra remote video parameters
     *
     * @param  array $params
     * @return VideoOutbound
     */
    public function addParams(array $params)
    {
        $this->params = array_replace($this->params, $params);
        return $this;
    }

    /**
     * Remove extra remote video parameters
     *
     * @param  array $keys
     * @return VideoOutbound
     */
    public function removeParams(array $keys)
    {
        $this->params = array_diff_key($this->params, array_flip($keys));
        return $this;
    }

    /**
     * Set an extra remote video parameter
     *
     * @param  string $key
     * @param  mixed  $value
     * @return VideoOutbound
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Get an extra remote video parameter
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        if (!isset($this->params[$key])) {
            return $default;
        }

        return $this->params[$key];
    }

    /**
     * Check if an extra remote video parameter exists
     *
     * @param  string $key
     * @return bool
     */
    public function hasParam($key)
    {
        return isset($this->params[$key]);
    }

    /**
     * Remove an extra remote video parameter
     *
     * @param  string $key
     * @return VideoOutbound
     */
    public function removeParam($key)
    {
        unset($this->params[$key]);
        return $this;
    }

    /**
     * Set the videofile for this outbound
     *
     * @param  OutboundVideoFile $videoFile
     * @return VideoInbound
     */
    public function setVideoFile(OutboundVideoFile $videoFile)
    {
        $this->videoFile = $videoFile;
        return $this;
    }

    /**
     * Get the videofile for this outbound
     *
     * @return OutboundVideoFile
     */
    public function getVideoFile()
    {
        return $this->videoFile;
    }
}
