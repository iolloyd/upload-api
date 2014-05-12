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
    use Traits\TimestampableTrait;

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
     * #Column(type="datetime", nullable=true)
     */
    protected $expiresAt;

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
     * Set the filename
     *
     * @param  string $filename
     * @return VideoInbound
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
     * @return VideoInbound
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

