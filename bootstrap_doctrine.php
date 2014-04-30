<?php
require_once "autoload.php";

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager; 
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Timestampable\TimestampableListener;

$isDevMode = true;

$conn = [ 
    'driver'   => 'pdo_mysql',
    'dbname'   => 'cloudxxx',
    'user'     => 'root',
    'password' => 'root', 
    'host'     => 'localhost',
];

$config = Setup::createAnnotationMetadataConfiguration([__DIR__."/src"], $isDevMode);

$addListener = function($eventManager, $reader, $listener) {
    $listener->setAnnotationReader($reader);
    $eventManager->addEventSubscriber($listener);
    return $eventManager;
};

$reader = new CachedReader(new AnnotationReader, new ArrayCache);
$eventManager = new EventManager(); 
$eventManager = $addListener($eventManager, $reader, new TimestampableListener);
$eventManager = $addListener($eventManager, $reader, new SluggableListener);
$entityManager = EntityManager::create($conn, $config, $eventManager);




