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
        $conn = [ 
            'driver'   => 'pdo_mysql',
            'dbname'   => 'cloudxxx',
            'user'     => 'root',
            'password' => 'root', 
            'host'     => 'localhost',
        ];

        $src = dirname(dirname(__DIR__)) . "/src";
        $config = Setup::createAnnotationMetadataConfiguration([$src], true);

        $em = EntityManager::create($conn, $config);

        //$pdo = $em->getConnection()->getWrappedConnection();

        $em->clear();

        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = $em->getMetaDataFactory()->getAllMetaData();

        $this->entityManager = $em;

        $tool->dropSchema($classes);
        $tool->createSchema($classes);

    }

}

