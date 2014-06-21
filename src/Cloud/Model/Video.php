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
 */
class Video extends AbstractModel
{
    /*
     * php-resque status codes:
     *
    const STATUS_WAITING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_COMPLETE = 4;
     */

    const STATUS_DRAFT    = 'draft';
    const STATUS_PENDING  = 'pending';
    const STATUS_WORKING  = 'working';
    const STATUS_COMPLETE = 'complete';

    const ORIENTATION_GAY = 'gay';
    const ORIENTATION_SOLO = 'solo';
    const ORIENTATION_STRAIGHT = 'straight';

    use Traits\IdTrait;
    use Traits\CreatedAtTrait;
    use Traits\CreatedByTrait;
    use Traits\UpdatedAtTrait;
    use Traits\CompanyTrait;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({"details.videos"})
     */
    protected $completedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $description;


    /**
     * Inbound files: user upload from browser
     *
     * @ORM\OneToMany(
     *   targetEntity="VideoInbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"details"})
     */
    protected $inbounds;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Accessor(getter="isDraft")
     * @JMS\Groups({"list", "details"})
     */
    protected $isDraft = true;

    /**
     * Orientation of video. Example: Straight, solo, gay.
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $orientation;

    /**
     * Outbound files: worker publish to tubesite
     *
     * @ORM\OneToMany(
     *   targetEntity="VideoOutbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"details"})
     */
    protected $outbounds;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $publishedAt;

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
     * @ORM\Column(type="string")
     * @JMS\Groups({"list", "details"})
     */
    protected $status = self::STATUS_DRAFT;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $title;

    /**
     * @ORM\OneToOne(
     *   targetEntity="VideoStat",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"list", "details"})
     */
    protected $stats;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     * @JMS\Groups({"list", "details"})
     */
    protected $tags;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $thumbnail;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Version
     * @JMS\Groups({"details"})
     */
    protected $version = 1;

    /**
     * @ORM\OneToMany(targetEntity="Cloud\Model\VideoFile\InboundVideoFile", mappedBy="Video")
     */
    protected $inboundVideoFiles;

    /**
     * @ORM\OneToMany(targetEntity="Cloud\Model\VideoFile\OutboundVideoFile", mappedBy="Video")
     */
    protected $outboundVideoFiles;

    //////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     */
    public function __construct($user)
    {
        $this->tags = new ArrayCollection();
        $this->inbounds = new ArrayCollection();
        $this->outbounds = new ArrayCollection();
        $this->inboundVideoFiles = new ArrayCollection();
        $this->outboundVideoFiles = new ArrayCollection();
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
     * Get the orientation of the video
     *
     * @return string
     */
    public function getOrientation()
    {
      return $this->orientation;
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
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set the processing status
     *
     * @param  string $status
     * @throws \InvalidArgumentException
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
        $this->isDraft = ($status == self::STATUS_DRAFT);

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
        return $this->isDraft === true;
    }

    /**
     * Get the inbound file transfers for this video
     *
     * @return ArrayCollection
     */
    public function getInbounds()
    {
        return $this->inbounds;
    }

    /**
     * Get the outbound file transfers for this video
     *
     * @return ArrayCollection
     */
    public function getOutbounds()
    {
        return $this->outbounds;
    }

    /**
     * Returns the associated inbounds
     *
     * @return array
     */
    public function getVideoInbounds()
    {
        return $this->inbounds;
    }

    /**
     * Returns the associated outbounds
     *
     * @return array
     */
    public function getVideoOutbounds()
    {
        return $this->outbounds;
    }

    /**
     * Returns the video thumbnail image
     *
     * @return string
     */
    public function getThumbnail()
    {
      $thumbnails = ['tri', 'squ', 'sta', 'env',];
      $thumbnail = $thumbnails[rand(0, count($thumbnails)-1)] . '.png';

      return $thumbnail;
    }

    /**
     * Sets the video thumbnail
     *
     * @param string $thumbnail
     *
     * @return $this
     */
    public function setThumbnail($thumbnail)
    {
      $this->thumbnail = $thumbnail;

      return $this;
    }

    /**
     * @return DateTime
     */
    public function getPublishedAt()
    {
      return $this->publishedAt;
    }

    /**
     * Sets the published date
     *
     * @param  DateTime $date
     *
     * @return Video
     */
    public function setPublishedAt(\DateTime $date)
    {
      $this->publishedAt = $date;

      return $this;
    }

    /**
     * @return DateTime
     */
    public function getCompletedAt()
    {
      return $this->completedAt;
    }

    /**
     * @param DateTime $date the date of completion
     *
     * @return Video
     */
    public function setCompletedAt(\DateTime $date)
    {
      $this->completedAt = $date;
      return $this;
    }

    /**
     * @param string $tags a json encoded array of tags
     *
     * @return Video
     */
    public function setTags($tags)
    {
      $this->tags->clear();

      foreach ($tags as $tag) {
          $this->addTag($tag);
      }

      return $this;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Overrides method in SlugTrait
     *
     * @return array
     */
    protected function getSlugFields()
    {
        return ['id', 'title'];
    }

    /**
     * @return bool
     */
    protected function shouldRegenerateSlugOnUpdate()
    {
        return $this->isDraft();
    }

}
