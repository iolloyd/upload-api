<?php

namespace Cloud\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="video")
 **/
class Video
{
    const STATUS_PENDING  = 'pending';
    const STATUS_WORKING  = 'working';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';

    /** 
     * @Id @Column(type="integer") @GeneratedValue 
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="video",cascade={"persist"})
     */
    protected $creator;

    /** 
     * @Column(type="string")
     */
    protected $filename;

    /** 
     * @ManyToMany(targetEntity="Tag", inversedBy="tag", cascade={"persist"})
     */
    protected $tags;

    /**
     * @Column(type="string")
     */
    protected $status;

    /** 
     * @OneToMany(targetEntity="VideoInbound", mappedBy="video", cascade={"persist"}) 
     * @JoinColumn(name="video_id", referencedColumnName="id") 
     */
    protected $videoInbounds;

    /** 
     * @OneToMany(targetEntity="VideoOutbound", mappedBy="video", cascade={"persist"})
     * @JoinColumn(name="video_id", referencedColumnName="id") 
     */
    protected $videoOutbounds;

    public function __construct()
    {
        $this->tags = new ArrayCollection;
        $this->videoInbounds = new ArrayCollection;
        $this->videoOutbounds = new ArrayCollection;
    }

    public function addVideoInbound(VideoInbound $videoInbound)
    {
        $videoInbound->setVideo($this);
        $this->videoInbounds[] = $videoInbound;
    }


    public function addVideoOutbound(VideoOutbound $videoOutbound)
    {
        $videoOutbound->setVideo($this);
        $this->videoOutbounds[] = $videoOutbound;
    }

    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getVideoInbounds()
    {
        return $this->videoInbounds;
    }

    public function getVideoOutbounds()
    {
        return $this->videoOutbounds;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function setStatus($status)
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_WORKING,
            self::STATUS_COMPLETE,
            self::STATUS_ERROR,
        ])
        ) {
            throw new \InvalidArgumentException(
                "Invalid Status"
            );
        }
        $this->status = $status;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

}
