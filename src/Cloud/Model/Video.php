<?php

namespace Cloud\Model;
use Cloud\Model\AbstractModel;

class Video extends AbstractModel 
{
    protected $name = 'video';

    public $path;
    public $title;
    public $description;
    public $tags;

}

