<?php
namespace CloudTest;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Gedmo\Timestampable\TimestampableListener;

require_once dirname(dirname(__DIR__)) . "/vendor/autoload.php";

abstract class Model extends \PHPUnit_Framework_TestCase
{
    protected $entityManager;

    public function setup()
    {
        $connection = $this->setupConnection();
    }

    public function teardown()
    {
    }

    protected function setupConnection()
    {
        $src = dirname(dirname(__DIR__)) . "/src";
        $config = Setup::createAnnotationMetadataConfiguration([$src], true);
        $em = $this->getEntityManager($config);
        $this->entityManager = $em;
        $this->setupTimeStampListener($em, $config);
        $this->setupSchema($em);

    }

    protected function getEntityManager($config)
    {
        /*
        $defaultReader= $this->getDefaultReader($config);
        $driverChain = $this->getDriverChain($config);
        $config->setMetadataDriverImpl($driverChain);
        \Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain, $defaultReader);
        $connectionParams = $this->getConfiguration();
        $em = EntityManager::create($connectionParams, $config);
        $em->clear();
         */
        return $em;
    }

    protected function setupTimeStampListener($em, $config)
    {
        $defaultReader = $this->getDefaultReader($config);
        $timestampableListener = new TimestampableListener();
        $timestampableListener->setAnnotationReader($defaultReader);

        $evm = $em->getEventManager();
        $evm->addEventSubscriber($timestampableListener);
    }

    protected function setupSchema($em)
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $classes = $em->getMetaDataFactory()->getAllMetaData();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    protected function getDefaultReader($config)
    {
        return $this->getDefaultDriver($config)->getReader();
    }

    protected function getDefaultDriver($config)
    {
        return $config->getMetadataDriverImpl();
    }

    protected function getDriverChain($config)
    {
        $driver = $this->getDefaultDriver($config);
        $driverChain = new MappingDriverChain();
        $driverChain->setDefaultDriver($driver);
        return $driverChain;
    }

    protected function setupMapping($config, $driverChain)
    {
        $defaultReader= $this->getDefaultReader($config);
        $config->setMetadataDriverImpl($driverChain);
        \Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain, $defaultReader);

        return $config;
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

