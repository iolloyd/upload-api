<?php

namespace Cloud\Model;

/**
 * @Entity @Table(name="video_outbound")
 **/
class VideoOutbound
{
    const STATUS_PENDING = 'pending';
    const STATUS_WORKING = 'working';
    const STATUS_COMPLETE = 'complete';
    const STATUS_ERROR = 'error';

    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /**
     * @var int
     */
    public $videoId;


    /** @ManyToOne(targetEntity="video", inversedBy="videoOutbounds") 
     * @JoinColumn(name="video_id", referencedColumnName="id") 
     */
    protected $video;
    /**
     * @var int
     */
    public $userId;

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

