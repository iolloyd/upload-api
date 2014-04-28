<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;

$conn = [ 
    'driver' => 'pdo_mysql',
    'dbname' => 'cloudxxx',
    'user' => 'root',
    'password' => 'root', 
    'host' => 'localhost',
];

$config = Setup::createAnnotationMetadataConfiguration(
    [__DIR__."/src"], 
    $isDevMode
);
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);

$app->entityManager = EntityManager::create($conn, $config);
