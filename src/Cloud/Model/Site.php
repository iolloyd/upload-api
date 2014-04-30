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
     * @OneToMany(targetEntity="VideoInbound", mappedBy="site")
     */
    protected $videoInbounds;

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
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
