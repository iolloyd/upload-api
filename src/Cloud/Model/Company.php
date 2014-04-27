<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class Company extends AbstractModel
{
    protected $oneToMany = ['user'];

    public $title;
    public $contactName;
    public $contactEmail;
}


