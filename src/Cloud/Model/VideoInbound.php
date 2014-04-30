<?php

namespace Cloud\Model;

/**
 * @Entity @Table(name="video_inbound")
 **/
class VideoInbound
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
     * @ManyToOne(targetEntity="Video",inversedBy="videoInbound",cascade={"persist"}) 
     */
    protected $video;

    /** @ManyToOne(targetEntity="Site", inversedBy="videoInbounds") 
     * @JoinColumn(name="site_id", referencedColumnName="id") 
     */
    protected $site;

    public function getVideo()
    {
        return $this->video;
    }

    public function setVideo($video)
    {
        $this->video = $video;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getStatus()
    {
        return $this->status;
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

}

