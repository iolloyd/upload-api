<?php
/**
 * @package  cloudxxx-api (http://www.cloud.xxx)
 *
 * @author    ReallyUseful <info@ruseful.com>
 * @copyright 2014 Really Useful Limited
 * @license   Proprietary code. Usage restrictions apply.
 */

namespace Cloud\Model;

use DateTime;
use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Video extends AbstractModel implements JsonSerializable
{
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_WORKING = 'working';

    const STATUS_COMPLETE = 'complete';

    use Traits\IdTrait;
    use Traits\SlugTrait;
    use Traits\TimestampableTrait;

    /**
     * @Column(type="integer")
     * @Version
     */
    protected $version = 1;

    /**
     * #JoinColumn(nullable=false)
     * @ManyToOne(targetEntity="Company")
     */
    protected $company;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ManyToMany(targetEntity="Tag",cascade={"persist"})
     */
    protected $tags;

    /**
     * The overall processing status for this video by the worker system. To
     * query success or failure data, look at each individual inbound and
     * outbound and query their status.
     *
     * @see STATUS_DRAFT
     * @see STATUS_PENDING
     * @see STATUS_WORKING
     * @see STATUS_COMPLETE
     *
     * @Column(type="string", length=16)
     */
    protected $status = self::STATUS_DRAFT;

    /**
     * @Column(type="boolean")
     */
    protected $isDraft = true;

    /**
     * Inbound files: user upload from browser
     *
     * @OneToMany(
     *   targetEntity="VideoInbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $inbounds;

    /**
     * Outbound files: worker publish to tubesite
     *
     * @OneToMany(
     *   targetEntity="VideoOutbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $outbounds;

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
     * @Column(type="datetime", nullable=true)
     */
    protected $publishedAt;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $completedAt;

     
    //////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->inbounds = new ArrayCollection();
        $this->outbounds = new ArrayCollection();
    }

    /**
     * Get the entity revision
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the creating user
     *
     * @param  User $created_by
     * @return Video
     */
    public function setCreatedBy(User $created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * Get the creating user
     *
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Set the updating user
     *
     * @param  User $updated_by
     * @return Video
     */
    public function setUpdatedBy(User $updated_by)
    {
        $this->updated_by = $updated_by;
        return $this;
    }

    /**
     * Get the updating user
     *
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updated_by;
    }

    /**
     * Set the parent company
     *
     * @param  Company $company
     * @return Video
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
     * Set the video title
     *
     * @param  string $title
     * @return Video
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the video title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the video description
     *
     * @param  string $description
     * @return Video
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the video description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add a tag
     *
     * @param  Tag $tag
     * @return Video
     */
    public function addTag(Tag $tag)
    {
        $this->tags->add($tag);
        return $this;
    }

    /**
     * Remove a tag
     *
     * @param  Tag $tag
     * @return Video
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    /**
     * Get the tags
     *
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set the processing status
     *
     * @param  string $status
     * @return Video
     */
    public function setStatus($status)
    {
        if (!in_array($status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_WORKING,
            self::STATUS_COMPLETE
        ])) {
            throw new \InvalidArgumentException("Invalid status");
        }

        $this->status = $status;
        $this->is_draft = ($status == self::STATUS_DRAFT);

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
     * Check if the video is a draft and can be edited
     *
     * @return bool
     */
    public function isDraft()
    {
        return $this->isDraft;
    }

    /**
     * Get the inbound file transfers for this video
     *
     * @return Collection
     */
    public function getInbounds()
    {
        return $this->inbounds;
    }

    /**
     * Get the outbound file transfers for this video
     *
     * @return Collection
     */
    public function getOutbounds()
    {
        return $this->outbounds;
    }

    /**
     * Get the raw video file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Get the file type 
     *
     * @return string
     */
    public function getFileType()
    {
        return $this->filetype;
    }

    /**
     * Get the file size
     *
     * @return string
     */
    public function getFileSize()
    {
        return $this->filesize;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * @return array
     */
    protected function getSlugFields()
    {
        return ['id', 'title'];
    }

    /**
     * Set the raw video file name
     *
     * @param  string $filename
     * @return Video
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return bool
     */
    protected function shouldRegenerateSlugOnUpdate()
    {
        return $this->isDraft();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id'          => $this->getId(),
            'version'     => $this->getVersion(),
            'created_at'  => $this->getCreatedAt()->format(DateTime::ISO8601),
            'updated_at'  => $this->getUpdatedAt()->format(DateTime::ISO8601),
            'status'      => $this->getStatus(),
            'is_draft'    => $this->isDraft(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'tags'        => $this->getTags()->toArray(),
        ];
    }

    public function getVideoInbounds()
    {
        return $this->inbounds;
    }

    public function getVideoOutbounds()
    {
        return $this->outbounds;
    }

}
