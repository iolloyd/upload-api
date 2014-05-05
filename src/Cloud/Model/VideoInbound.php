<?php

namespace Cloud\Model;

use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class VideoInbound extends AbstractModel
{
    /** Waiting for chunk files to be uploaded */
    const STATUS_PENDING = 'pending';

    /** Finalizing upload and combining chunks into one file */
    const STATUS_WORKING = 'working';

    /** Upload complete */
    const STATUS_COMPLETE = 'complete';

    /** Error during upload or finalization */
    const STATUS_ERROR = 'error';

    //////////////////////////////////////////////////////////////////////////

    use Traits\IdTrait;

    /**
     * @Column(type="string", length=48)
     */
    protected $token;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Video", inversedBy="inbounds")
     */
    protected $video;

    /**
     * #Column(type="datetime")
     */
    protected $created_at;

    /**
     * #JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="User")
     */
    protected $created_by;

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
     * #Column(type="datetime", nullable=true)
     */
    protected $expires_at;

    /**
     * Constructor
     */
    public function __construct(Video $video = null)
    {
        $generator = new UriSafeTokenGenerator();
        $this->setToken($generator->generateToken());

        if ($video) {
            $this->setVideo($video);
        }
    }

    /**
     * Set the upload identification token
     *
     * @param  string $token
     * @return VideoInbound
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get the upload identification token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the parent video
     *
     * @param  Video $video
     * @return VideoInbound
     */
    public function setVideo($video)
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
     * Get the AWS S3 storage prefix or directory name
     *
     * @return string
     */
    public function getStorageChunkPath()
    {
        return sprintf('inbounds/%d/%d/%s',
            $this->getVideo()->getId(),
            $this->getId(),
            $this->getToken()
        );
    }

    /**
     * @return string
     */
    public function getStorageFilePath()
    {
        return sprintf('inbounds/%d/%d/%s',
            $this->getVideo()->getId(),
            $this->getId(),
            $this->getFilename()
        );
    }
}

