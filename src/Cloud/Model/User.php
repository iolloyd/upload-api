<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class User extends AbstractModel
{
    protected $oneToMany = ['video'];

    public $username;
    public $email;
    public $password;

}

