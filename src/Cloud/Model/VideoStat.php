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

use Doctrine\ORM\Mapping as ORM;
use Cloud\Doctrine\Annotation as CX;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class VideoStat extends AbstractModel
{
    use Traits\IdTrait;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\OneToOne(targetEntity="Video", mappedBy="stat")
     */
    protected $video;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Groups({"list", "details", "list.videos", "details.videos"})
     */
    protected $plays;

    /**
     * @ORM\Column(type="float")
     * @JMS\Groups({"list", "details", "list.videos", "details.videos"})
     */
    protected $rating;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Groups({"list", "details", "list.videos", "details.videos"})
     */
    protected $clicks;

    public function getPlays()
    {
        return $this->plays;
    }

    public function setPlays($plays)
    {
        $this->plays = $plays;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    public function getClicks()
    {
        return $this->clicks;
    }

    public function setClicks($clicks)
    {
        $this->clicks = $clicks;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function setVideo($video)
    {
        $this->video = $video;
    }
}
