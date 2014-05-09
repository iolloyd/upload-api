<?php

namespace Cloud\Model;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class VideoOutbound extends AbstractModel
{
    /** Queued for worker */
    const STATUS_PENDING = 'pending';

    /** Currently preparing and publishing to tubesite */
    const STATUS_WORKING = 'working';

    /** Publishing complete */
    const STATUS_COMPLETE = 'complete';

    /** Error during preparation or publishing */
    const STATUS_ERROR = 'error';

    //////////////////////////////////////////////////////////////////////////

    use Traits\IdTrait;
    use Traits\TimestampableTrait;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Video", inversedBy="outbounds")
     */
    protected $video;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Company")
     */
    protected $company;

    /**
     * @see STATUS_PENDING
     * @see STATUS_WORKING
     * @see STATUS_COMPLETE
     * @see STATUS_ERROR
     *
     * @Column(type="string", length=16)
     */
    protected $status = self::STATUS_PENDING;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Tubesite")
     */
    protected $tubesite;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="TubesiteUser")
     */
    protected $tubesiteUser;

    /**
     * @Column(type="string")
     */
    protected $externalId;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $filename;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $filesize;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $filetype;

    /**
     * @Column(type="json_array")
     */
    protected $params = [];

    // TODO:
    // transferStatus
    // submitStatus
    // approvalStatus

    public function __construct()
    {
        $this->status = STATUS_PENDING;
        parent::__construct();
    }

    /**
     * Set the parent video
     *
     * @param  Video $video
     * @return VideoOutbound
     */
    public function setVideo(Video $video)
    {
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
     * Set the filename
     *
     * @param  string $filename
     * @return VideoOutbound
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Get the filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the filesize in bytes
     *
     * @param  int $filesize
     * @return VideoOutbound
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;
        return $this;
    }

    /**
     * Get the filesize in bytes
     *
     * @return int
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set the file mimetype
     *
     * @param  string $filetype
     * @return VideoOutbound
     */
    public function setFiletype($filetype)
    {
        $this->filetype = $filetype;
        return $this;
    }

    /**
     * Get the file mimetype
     *
     * @return string
     */
    public function getFiletype()
    {
        return $this->filetype;
    }
}
