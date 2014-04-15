<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class User extends AbstractModel
{
    protected name = 'user';

    public $username;
    public $email;
    public $password;

}

