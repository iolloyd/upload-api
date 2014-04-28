<?php

namespace Cloud\Model;


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
     * @Column(type="string")
     */
    protected $slug;

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

}

