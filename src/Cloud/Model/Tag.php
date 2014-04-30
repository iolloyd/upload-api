<?php

namespace Cloud\Model;

use Cloud\Model\Video;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity #Table(name='tag')
 **/
class Tag 
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** 
     * @Column(type="string")
     */
    protected $title;

    /**
     * @ManyToMany(targetEntity="Video", mappedBy="tag")
     */
    protected $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function addVideo(Video $video)
    {
        $video->addTag($this);
        $this->videos[] = $video;
    }
}

