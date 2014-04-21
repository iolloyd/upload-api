<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class Video extends AbstractModel 
{
    protected $tableName = 'video';
    protected $manyToMany = ['tag'];

    public $path;
    public $title;
    public $description;

}
