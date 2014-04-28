<?php

namespace Cloud\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="video")
 **/
class Video
{
    const STATUS_PENDING = 'pending';
    const STATUS_WORKING = 'working';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';

    /** 
     * @Id @Column(type="integer") @GeneratedValue 
     */
    protected $id;

    /** 
     * @Column(type="string)
     */
    protected $filename;

    /** @ManyToMany(targetEntity="Tag") **/
    protected $tags;

    /**
     * @Column(type="varchar")
     */
    protected $status;

    /** 
     * @OneToMany(targetEntity="VideoInbound", mappedBy="video") 
     */
    protected $videoInbounds;

    /** 
     * @OneToMany(targetEntity="VideoOutbound", mappedBy="video") 
     * @JoinColumn(name="video_id", referencedColumnName="id") 
     */
    protected $videoOutbounds;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="videos")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $creator;

    public function __construct()
    {
        $this->tags = new ArrayCollection;
        $this->videoInbounds = new ArrayCollection;
        $this->videoOutbounds = new ArrayCollection;

    }

    public function setFilename($path)
    {
        $this->filename = $path;
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

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    public function addInbound(VideoInbound $videoInbound)
    {
        $this->videoInbounds[] = $videoInbound;
    }

    public function addOutbound(VideoOutbound $videoOutbound)
    {
        $this->videoOutbounds[] = $videoOutbound;
    }

}
