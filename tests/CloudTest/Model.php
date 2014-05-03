<?php
namespace Tests;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "bootstrap_doctrine.php";

abstract class Model extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $connection = $this->getConnection();
    }

    public function teardown()
    {
    }

    protected function getConnection()
    {
        $src = dirname(dirname(__DIR__)) . "/src";
        $config = Setup::createAnnotationMetadataConfiguration([$src], true);
        $conn = $this->getConfiguration();
        $em = EntityManager::create($conn, $config);

        $em->clear();

        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = $em->getMetaDataFactory()->getAllMetaData();

        $this->entityManager = $em;

        $tool->dropSchema($classes);
        $tool->createSchema($classes);

    }

    protected function getConfiguration()
    {
        $conn = [ 
            'driver'   => 'pdo_mysql',
            'dbname'   => 'test_cloudxxx',
            'user'     => 'root',
            'password' => 'root', 
            'host'     => 'localhost',
        ];

        return $conn;
    }

}

