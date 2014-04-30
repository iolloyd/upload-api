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

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

}

