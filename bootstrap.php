<?php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once dirname(__FILE__).'/vendor/autoload.php';
$entities = array('./lib/entities');
$config = parse_ini_file(dirname(__FILE__).'/config/config.ini', true);
$dbParams = array(
    'driver'   => $config['db']['driver'],
    'user'     => $config['db']['user'],
    'password' => $config['db']['password'],
    'dbname'   => $config['db']['dbname'],
);

$isDevMode = true;
$dbConfig = Setup::createAnnotationMetadataConfiguration($entities, $isDevMode);
$entityManager = EntityManager::create($dbParams, $dbConfig);

