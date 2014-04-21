<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class User extends AbstractModel
{
    protected $tableName = 'user';

    public $username;
    public $email;
    public $password;

    public $oneToMany = ['user'];
}

