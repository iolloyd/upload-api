<?php

namespace Cloud\Model;

/**
 * @Entity #Table(name='site')
 **/
class Site
{
    /** 
     * @Id @Column(type="integer") @GeneratedValue 
     */
    protected $id;

    /** 
     * @Column(type="string")
     */
    protected $title;

    /** 
     * @Column(type="string")
     */
    protected $slug;

    /** 
     * @Column(type="string")
     */
    protected $uploadUrl;

    /**
     * @ManyToMany(targetEntity="VideoOutbound", mappedBy="site", cascade={"persist"})
     */
    protected $videoOutbounds;

    public function addVideoOutbound(VideoOutbound $videoOutbound)
    {
        $this->videoOutbounds[] = $videoOutbound;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getVideoOutbounds()
    {
        return $this->videoOutbounds;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setUploadUrl($uploadUrl)
    {
        $this->uploadUrl = $uploadUrl;
    }

    public function getUploadUrl()
    { 
        return $this->uploadUrl;
    }

}
