<?php

namespace Cloud\Model;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="user")
 **/
class User 
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id;

    /** 
     * @ManyToOne(targetEntity="company", inversedBy="users")
     * @JoinColumn(name="company_id", referencedColumnName="id")
     */
    protected $company;

    /** @Column(type="string") **/
    protected $email;

    /** @Column(type="string") **/
    protected $password;

    /**
     * @OneToMany(targetEntity="video", mappedBy="user")
     */
    protected $videos;

    /**
     * @OneToMany(targetEntity="Video", mappedBy="user")
     */
    public function __construct()
    {
        $this->videos = new ArrayCollection;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = crypt($password);
    }

    public function verifyPassword($guess)
    {
        $crypted = crypt($guess);
        return crypt($guess, $crypted) == $this->password;
    }

    public function addVideo(Video $video)
    {
        $this->videos[] = $video;
    }


}

