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
use InvalidArgumentException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 * @JMS\AccessType("public_method")
 */
class Video extends AbstractModel
{
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
    use Traits\SiteTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Version
     * @JMS\Groups({"details"})
     * @JMS\ReadOnly
     */
    protected $version = 1;

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
     * @ORM\Column(type="boolean")
     * @JMS\Accessor(getter="isDraft")
     * @JMS\Groups({"list", "details"})
     * @JMS\ReadOnly
     */
    protected $isDraft = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({"details.videos"})
     */
    protected $completedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $publishedAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $description;

    /**
     * Orientation of video. Example: Straight, solo, gay.
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $orientation = self::ORIENTATION_STRAIGHT;

    /**
     * Inbounds: user upload from browser
     *
     * @ORM\OneToMany(
     *   targetEntity="VideoInbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"details"})
     * @JMS\ReadOnly
     */
    protected $inbounds;

    /**
     * Outbounds: worker publish to tubesite
     *
     * @ORM\OneToMany(
     *   targetEntity="VideoOutbound",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"details"})
     * @JMS\ReadOnly
     */
    protected $outbounds;

    /**
     * @ORM\OneToOne(
     *   targetEntity="VideoStat",
     *   mappedBy="video",
     *   cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"list", "details"})
     * @JMS\ReadOnly
     */
    protected $stats;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @JMS\Groups({"list", "details"})
     */
    protected $primaryCategory;

    /**
     * @ORM\ManyToMany(targetEntity="Category")
     * @ORM\JoinTable(name="video_category")
     * @JMS\Groups({"list", "details"})
     * @JMS\ReadOnly
     */
    protected $secondaryCategories;

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
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Groups({"list", "details"})
     */
    protected $duration;

    //////////////////////////////////////////////////////////////////////////

    /**
     * Constructor
     *
     * @param Site $site  parent site entity this video belongs to
     */
    public function __construct(Site $site = null)
    {
        $this->inbounds = new ArrayCollection();
        $this->outbounds = new ArrayCollection();
        $this->secondaryCategories = new ArrayCollection();
        $this->tags = new ArrayCollection();

        if ($site) {
            $this->setSite($site);
        }
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
     * Set the orientation
     *
     * @param  string $orientation
     * @return Video
     */
    public function setOrientation($orientation)
    {
        if (!in_array($orientation, [
            self::ORIENTATION_GAY,
            self::ORIENTATION_SOLO,
            self::ORIENTATION_STRAIGHT,
        ])) {
            throw new InvalidArgumentException("Invalid orientation");
        }

        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Get the orientation
     *
     * @return string
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Get the stats object
     *
     * @return VideoStat
     */
    public function getStats()
    {
        return $this->stats;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Set the primary category
     *
     * @param  Category $primaryCategory
     * @return Video
     */
    public function setPrimaryCategory(Category $primaryCategory = null)
    {
        $this->primaryCategory = $primaryCategory;
        return $this;
    }

    /**
     * Get the primary category
     *
     * @return Category
     */
    public function getPrimaryCategory()
    {
        return $this->primaryCategory;
    }

    /**
     * Set the secondary categories
     *
     * @param  array|\Traversable $categories
     * @return Video
     */
    public function setSecondaryCategories($categories)
    {
        $this->secondaryCategories->clear();

        foreach ($categories as $category) {
            $this->addSecondaryCategory($category);
        }

        return $this;
    }

    /**
     * Add a secondary category
     *
     * @param  Category $category
     * @return Video
     */
    public function addSecondaryCategory(Category $category)
    {
        $this->secondaryCategories->add($category);
        return $this;
    }

    /**
     * Remove a secondary category
     *
     * @param Category $category
     * @return Video
     */
    public function removeSecondaryCategory(Category $category)
    {
        $this->secondaryCategories->removeElement($category);
        return $this;
    }

    /**
     * Get the secondary categories
     *
     * @return Collection
     */
    public function getSecondaryCategories()
    {
        return $this->secondaryCategories;
    }

    /**
     * Get a combined collection of primary and secondary categories
     *
     * @return Collection
     */
    public function getAllCategories()
    {
        $result = $this->getSecondaryCategories()->toArray();

        if ($primary = $this->getPrimaryCategory()) {
            array_unshift($result, $primary);
        }

        return new ArrayCollection($result);
    }

    /**
     * Set the tags
     *
     * @param  array|\Traversable $tags
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

    //////////////////////////////////////////////////////////////////////////

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
            throw new InvalidArgumentException("Invalid status");
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
    public function setPublishedAt(DateTime $date = null)
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
    public function setCompletedAt(DateTime $date = null)
    {
      $this->completedAt = $date;
      return $this;
    }

    //////////////////////////////////////////////////////////////////////////

    /**
     * Set the video duration
     *
     * @param  float $duration  duration in seconds.milliseconds
     * @return Video
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * Get the video duration
     *
     * @return float  duration in seconds.milliseconds
     */
    public function getDuration()
    {
        if ($this->duration === null
            && $this->inbounds->count()
        ) {
            if (($inbound = $this->inbounds->last())
                && ($file = $inbound->getVideoFile())
            ) {
                $this->setDuration($file->getDuration());
            }
        }

        return $this->duration;
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
