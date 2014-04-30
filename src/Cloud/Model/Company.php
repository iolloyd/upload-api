<?php

namespace Cloud\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity #Table(name='company')
 **/
class Company
{

    /** 
     * @Id @Column(type="integer") @GeneratedValue 
     */
    protected $id;

    /** 
     * @Column(type="string") 
     */
    protected $title;

    /** @OneToMany(targetEntity="User", mappedBy="company",cascade={"persist"})
     */
    protected $users;

    public function __construct()
    {
        $this->users = new ArrayCollection;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function addUser(User $user)
    {
        $this->users[] = $user;
    }

}


