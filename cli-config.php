<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;

require_once 'bootstrap.php';
//autoload.php';

$entityManager = $app['em'];

return ConsoleRunner::createHelperSet($entityManager);



