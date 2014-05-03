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
     * @Column(type="datetime")
     */
    protected $created_at;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="User")
     */
    protected $created_by;

    /**
     * @JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Company")
     */
    protected $company;

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
}

